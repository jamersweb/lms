<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonWatchSession;
use App\Services\ContentGatingService;
use Illuminate\Http\Request;

class WatchSessionController extends Controller
{
    /**
     * Start a new watch session for a lesson.
     */
    public function start(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $course = $lesson->module->course;

        if (! $user->isEnrolledIn($course->id)) {
            abort(403, 'You must be enrolled in the course.');
        }

        if (! ContentGatingService::userCanAccessLesson($user, $lesson)) {
            abort(403, 'You are not allowed to access this lesson.');
        }

        $session = LessonWatchSession::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'started_at' => now(),
            'watch_time_seconds' => 0,
            'last_time_seconds' => 0,
            'seek_events_count' => 0,
            'max_playback_rate' => 1,
            'is_valid' => true,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        return response()->json([
            'session_id' => $session->id,
        ]);
    }

    /**
     * Heartbeat during an active watch session.
     */
    public function heartbeat(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
            'current_time' => ['required', 'numeric', 'min:0'],
            'playback_rate' => ['required', 'numeric', 'min:0.25', 'max:4'],
        ]);

        $user = $request->user();
        $course = $lesson->module->course;

        if (! $user->isEnrolledIn($course->id)) {
            abort(403, 'You must be enrolled in the course.');
        }

        if (! ContentGatingService::userCanAccessLesson($user, $lesson)) {
            abort(403, 'You are not allowed to access this lesson.');
        }

        $session = LessonWatchSession::where('id', $data['session_id'])
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        if ($session->ended_at) {
            return response()->json(['ignored' => true]);
        }

        $current = (float) $data['current_time'];
        $prev = (float) $session->last_time_seconds;
        $delta = max(0, $current - $prev);

        $heartbeatInterval = 5;
        $seekThreshold = $heartbeatInterval + 4; // 5s interval + 4s slack

        $isSeek = $delta > $seekThreshold;
        if ($isSeek) {
            $session->seek_events_count = ($session->seek_events_count ?? 0) + 1;
        }

        // Cap credit per heartbeat to avoid huge jumps being fully counted
        $creditedSeconds = max(0, min($delta, $heartbeatInterval * 2));

        $session->watch_time_seconds = (int) $session->watch_time_seconds + (int) round($creditedSeconds);
        $session->last_time_seconds = $current;
        $session->max_playback_rate = max((float) $session->max_playback_rate, (float) $data['playback_rate']);
        $session->save();

        // Update lesson_progress aggregate
        $progress = LessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'is_completed' => false,
                'last_position_seconds' => 0,
            ]
        );

        $progress->last_position_seconds = max($progress->last_position_seconds ?? 0, (int) floor($current));
        $progress->time_watched_seconds = (int) $progress->time_watched_seconds + (int) round($creditedSeconds);
        $progress->last_heartbeat_at = now();
        $progress->max_playback_rate_seen = max((float) $progress->max_playback_rate_seen, (float) $data['playback_rate']);

        if ($isSeek) {
            $progress->seek_detected = true;
        }

        $progress->save();

        return response()->json([
            'ok' => true,
            'session_watch_time_seconds' => $session->watch_time_seconds,
            'progress_time_watched_seconds' => $progress->time_watched_seconds,
        ]);
    }

    /**
     * End a watch session.
     */
    public function end(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $session = LessonWatchSession::where('id', $data['session_id'])
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        if (! $session->ended_at) {
            $session->ended_at = now();
            $session->save();
        }

        return response()->json(['ended' => true]);
    }
}


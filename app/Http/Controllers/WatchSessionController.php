<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Services\ActivityLogger;
use App\Services\ProgressionService;
use App\Services\WatchTrackingService;
use Illuminate\Http\Request;

class WatchSessionController extends Controller
{
    public function __construct(
        private ProgressionService $progressionService,
        private WatchTrackingService $watchTrackingService,
        private ActivityLogger $activityLogger
    ) {}

    /**
     * Start a new watch session for a lesson.
     *
     * POST /lessons/{lesson}/watch/start
     */
    public function start(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $course = $lesson->module->course;

        // Allow free preview lessons without enrollment
        if (!$lesson->is_free_preview && !$user->isEnrolledIn($course->id)) {
            abort(403, 'You must be enrolled in the course.');
        }

        // Check progression (combines eligibility + sequential unlocking)
        $progression = $this->progressionService->canAccessLesson($user, $lesson);
        if (!$progression->allowed) {
            if (in_array('previous_lesson_incomplete', $progression->reasons)) {
                abort(403, 'Complete previous lesson first.');
            }
            abort(403, 'You are not allowed to access this lesson.');
        }

        $session = $this->watchTrackingService->startSession($user, $lesson);

        // Log watch session start
        $this->activityLogger->log(
            \App\Models\ActivityEvent::TYPE_LESSON_WATCH_STARTED,
            $user,
            [
                'subject' => $lesson,
                'course_id' => $course->id,
                'module_id' => $lesson->module_id,
                'lesson_id' => $lesson->id,
            ]
        );

        return response()->json([
            'session_id' => $session->id,
            'server_time' => now()->toIso8601String(),
            'heartbeat_interval' => 15,
        ]);
    }

    /**
     * Heartbeat during an active watch session.
     *
     * POST /lessons/{lesson}/watch/heartbeat
     */
    public function heartbeat(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
            'position_seconds' => ['required', 'numeric', 'min:0'],
            'playback_rate' => ['required', 'numeric', 'min:0.25', 'max:4'],
            'played_delta_seconds' => ['nullable', 'integer', 'min:0'],
            'visibility' => ['nullable', 'string', 'in:visible,hidden'],
            'is_seeking' => ['nullable', 'boolean'],
            'client_ts' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $course = $lesson->module->course;

        // Allow free preview lessons without enrollment
        if (!$lesson->is_free_preview && !$user->isEnrolledIn($course->id)) {
            abort(403, 'You must be enrolled in the course.');
        }

        // Check eligibility
        $eligibility = $this->eligibilityService->canAccessLesson($user, $lesson);
        if (!$eligibility->allowed) {
            abort(403, 'You are not allowed to access this lesson.');
        }

        $result = $this->watchTrackingService->recordHeartbeat($user, $lesson, $data);

        if (isset($result['ignored'])) {
            return response()->json($result);
        }

        return response()->json([
            'ok' => true,
            'watched_seconds' => $result['watched_seconds'],
            'seek_attempts' => $result['seek_attempts'],
        ]);
    }

    /**
     * End a watch session.
     *
     * POST /lessons/{lesson}/watch/end
     */
    public function end(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $this->watchTrackingService->endSession($user, $lesson, $data['session_id']);

        return response()->json(['ended' => true]);
    }
}

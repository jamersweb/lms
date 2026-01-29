<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonWatchSession;
use App\Models\LessonReflection;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $stalledDays = (int) $request->input('days', 3);

        // Active: heartbeat in last 10 minutes
        $activeUsers = User::whereHas('lessonProgress', function ($q) use ($now) {
            $q->where('last_heartbeat_at', '>=', $now->clone()->subMinutes(10));
        })->withCount(['lessonProgress as active_lessons_count' => function ($q) use ($now) {
            $q->where('last_heartbeat_at', '>=', $now->clone()->subMinutes(10));
        }])->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active_lessons_count' => $user->active_lessons_count,
            ];
        });

        // Stalled: no heartbeat/progress in N days
        $cutoff = $now->clone()->subDays($stalledDays);

        $stalledUsers = User::whereHas('lessonProgress', function ($q) {
                $q->whereNotNull('last_heartbeat_at')
                  ->orWhereNotNull('completed_at');
            })
            ->with(['lessonProgress' => function ($q) {
                $q->select('id', 'user_id', 'last_heartbeat_at', 'completed_at');
            }])
            ->get()
            ->filter(function (User $user) use ($cutoff) {
                $lastHeartbeat = $user->lessonProgress->max('last_heartbeat_at');
                $lastCompletion = $user->lessonProgress->max('completed_at');

                $lastActivity = collect([$lastHeartbeat, $lastCompletion])
                    ->filter()
                    ->map(fn ($v) => $v instanceof \DateTimeInterface ? $v : \Illuminate\Support\Carbon::parse($v))
                    ->max();

                return $lastActivity && $lastActivity->lt($cutoff);
            })
            ->map(function (User $user) use ($cutoff) {
                $lastHeartbeat = $user->lessonProgress->max('last_heartbeat_at');
                $lastCompletion = $user->lessonProgress->max('completed_at');

                $lastActivity = collect([$lastHeartbeat, $lastCompletion])
                    ->filter()
                    ->map(fn ($v) => $v instanceof \DateTimeInterface ? $v : \Illuminate\Support\Carbon::parse($v))
                    ->max();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'last_activity' => $lastActivity?->diffForHumans(),
                ];
            })
            ->values();

        // Speeding flags: seek, playback rate > 1.5, or verified but too little watch
        $speedingProgress = LessonProgress::with(['user', 'lesson'])
            ->where(function ($q) {
                $q->where('seek_detected', true)
                  ->orWhere('max_playback_rate_seen', '>', 1.5)
                  ->orWhere(function ($q2) {
                      $q2->where('verified_completion', true)
                          ->whereNotNull('time_watched_seconds');
                  });
            })
            ->get()
            ->filter(function (LessonProgress $progress) {
                if ($progress->seek_detected || $progress->max_playback_rate_seen > 1.5) {
                    return true;
                }

                $lesson = $progress->lesson;
                if (! $lesson || ! $lesson->video_duration_seconds) {
                    return false;
                }

                $ratio = $progress->time_watched_seconds / max($lesson->video_duration_seconds, 1);

                return $progress->verified_completion && $ratio < 0.6;
            })
            ->map(function (LessonProgress $progress) {
                $lesson = $progress->lesson;
                return [
                    'user' => [
                        'id' => $progress->user->id,
                        'name' => $progress->user->name,
                        'email' => $progress->user->email,
                    ],
                    'lesson' => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'course_title' => optional($lesson->module->course ?? null)->title,
                    ],
                    'flags' => [
                        'seek_detected' => (bool) $progress->seek_detected,
                        'max_playback_rate_seen' => $progress->max_playback_rate_seen,
                        'time_watched_seconds' => $progress->time_watched_seconds,
                        'video_duration_seconds' => $lesson->video_duration_seconds ?? null,
                        'verified_completion' => (bool) $progress->verified_completion,
                    ],
                ];
            })
            ->values();

        // Task Mastery View: Which tasks/Sunnah are students struggling with most
        $taskMastery = $this->getTaskMasteryData();

        return Inertia::render('Admin/Analytics/Index', [
            'activeUsers' => $activeUsers,
            'stalledUsers' => $stalledUsers,
            'speeding' => $speedingProgress,
            'stalledDays' => $stalledDays,
            'taskMastery' => $taskMastery,
        ]);
    }

    protected function getTaskMasteryData(): array
    {
        // Get lessons that require reflections (tasks)
        $lessonsWithReflections = Lesson::where('requires_reflection', true)
            ->with('reflections')
            ->get();

        $taskData = [];

        foreach ($lessonsWithReflections as $lesson) {
            $totalAttempts = $lesson->reflections()->count();
            $approvedCount = $lesson->reflections()->where('review_status', 'approved')->count();
            $needsClarificationCount = $lesson->reflections()->where('review_status', 'needs_clarification')->count();
            $pendingCount = $lesson->reflections()->where('review_status', 'pending')->count();

            if ($totalAttempts > 0) {
                $approvalRate = ($approvedCount / $totalAttempts) * 100;
                $struggleRate = (($needsClarificationCount + $pendingCount) / $totalAttempts) * 100;

                $taskData[] = [
                    'lesson_id' => $lesson->id,
                    'lesson_title' => $lesson->title,
                    'course_title' => optional($lesson->module->course)->title,
                    'total_attempts' => $totalAttempts,
                    'approved' => $approvedCount,
                    'needs_clarification' => $needsClarificationCount,
                    'pending' => $pendingCount,
                    'approval_rate' => round($approvalRate, 2),
                    'struggle_rate' => round($struggleRate, 2),
                ];
            }
        }

        // Sort by struggle rate (highest first)
        usort($taskData, fn($a, $b) => $b['struggle_rate'] <=> $a['struggle_rate']);

        return $taskData;
    }

    public function userActivity(User $user)
    {
        $sessions = LessonWatchSession::with('lesson.module.course')
            ->where('user_id', $user->id)
            ->latest('started_at')
            ->limit(50)
            ->get()
            ->map(function (LessonWatchSession $session) {
                $lesson = $session->lesson;
                $course = optional($lesson->module->course ?? null);

                return [
                    'id' => $session->id,
                    'lesson_title' => $lesson->title ?? 'Unknown',
                    'course_title' => $course->title ?? null,
                    'started_at' => optional($session->started_at)?->toDateTimeString(),
                    'ended_at' => optional($session->ended_at)?->toDateTimeString(),
                    'watch_time_seconds' => $session->watch_time_seconds,
                    'seek_events_count' => $session->seek_events_count,
                    'max_playback_rate' => $session->max_playback_rate,
                    'is_valid' => $session->is_valid,
                ];
            });

        return Inertia::render('Admin/Analytics/UserActivity', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'sessions' => $sessions,
        ]);
    }
}

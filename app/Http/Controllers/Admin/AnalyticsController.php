<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\DailyUserMetric;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();

        // Active users today (distinct users with activity_events today)
        $activeUsersToday = ActivityEvent::whereDate('occurred_at', $today)
            ->whereNotNull('user_id')
            ->distinct()
            ->count('user_id');

        // Stagnant users: count users whose latest metric shows stagnation
        $stagnantThreshold = config('notifications_lms.stagnation.inactive_days', 3);
        $threshold = now()->subDays($stagnantThreshold);

        $stagnantUsers = User::where('is_admin', false)
            ->where(function ($q) use ($threshold) {
                $q->whereHas('latestMetric', function ($metricQuery) use ($threshold) {
                    $metricQuery->where(function ($q2) use ($threshold) {
                        $q2->where('last_activity_at', '<', $threshold)
                           ->orWhere(function ($q3) use ($threshold) {
                               $q3->whereNull('last_activity_at')
                                  ->where('date', '<', $threshold->toDateString());
                           });
                    });
                })
                ->orWhereDoesntHave('latestMetric')
                ->orWhere('last_active_at', '<', $threshold)
                ->orWhereNull('last_active_at');
            })
            ->count();

        // Total watched seconds today
        $watchedSecondsToday = DailyUserMetric::where('date', $today)
            ->sum('watched_seconds');

        // Violations today
        $violationsToday = DailyUserMetric::where('date', $today)
            ->sum('violations_count');

        // Pending reflections count
        $pendingReflections = LessonReflection::where('review_status', 'pending')
            ->count();

        // Open questions count
        $openQuestions = Question::where('status', Question::STATUS_OPEN)
            ->count();

        return Inertia::render('Admin/Analytics/Index', [
            'metrics' => [
                'active_users_today' => $activeUsersToday,
                'stagnant_users' => $stagnantUsers,
                'watched_seconds_today' => $watchedSecondsToday,
                'violations_today' => $violationsToday,
                'pending_reflections' => $pendingReflections,
                'open_questions' => $openQuestions,
            ],
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

<?php

namespace App\Jobs;

use App\Models\DailyUserMetric;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\LessonWatchSession;
use App\Models\TaskCheckin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateDailyUserMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?Carbon $date = null
    ) {
        // Default to yesterday if not provided
        $this->date = $date ?? Carbon::yesterday();
    }

    public function handle(): void
    {
        $date = $this->date->toDateString();
        $startOfDay = $this->date->startOfDay();
        $endOfDay = $this->date->copy()->endOfDay();

        // Get all users who had activity on this date
        $userIds = DB::table('activity_events')
            ->whereDate('occurred_at', $date)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        // Also include users with watch sessions, progress, reflections, or checkins
        $additionalUserIds = collect()
            ->merge(DB::table('lesson_watch_sessions')->whereDate('started_at', $date)->distinct()->pluck('user_id'))
            ->merge(DB::table('lesson_progress')->whereDate('completed_at', $date)->distinct()->pluck('user_id'))
            ->merge(DB::table('lesson_reflections')->whereDate('created_at', $date)->distinct()->pluck('user_id'))
            ->merge(DB::table('task_checkins')->whereDate('checkin_on', $date)->distinct()->pluck('task_progress_id'))
            ->map(function ($taskProgressId) {
                return $taskProgressId ? DB::table('task_progress')->where('id', $taskProgressId)->value('user_id') : null;
            })
            ->filter()
            ->unique()
            ->toArray();

        $userIds = array_unique(array_merge($userIds, $additionalUserIds));

        if (empty($userIds)) {
            return; // No users with activity on this date
        }

        // Batch load all users at once
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($userIds as $userId) {
            if (!isset($users[$userId])) {
                continue;
            }

            // Aggregate metrics using single queries per metric type
            $watchedSeconds = (int) LessonWatchSession::where('user_id', $userId)
                ->whereDate('started_at', $date)
                ->sum('watched_seconds');

            $lessonsCompleted = LessonProgress::where('user_id', $userId)
                ->whereDate('completed_at', $date)
                ->count();

            $reflectionsSubmitted = LessonReflection::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->count();

            // Task checkins: count checkins for this user's task progress
            $taskCheckins = TaskCheckin::whereHas('taskProgress', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->whereDate('checkin_on', $date)
                ->count();

            // Violations: count from watch sessions and progress
            // Load sessions once for both violations and active_seconds
            $sessions = LessonWatchSession::where('user_id', $userId)
                ->whereDate('started_at', $date)
                ->get(['id', 'started_at', 'ended_at', 'watched_seconds', 'violations']);

            $violationsCount = 0;
            foreach ($sessions as $session) {
                if ($session->violations && is_array($session->violations)) {
                    $violationsCount += count($session->violations);
                }
            }

            $progressRecords = LessonProgress::where('user_id', $userId)
                ->whereDate('updated_at', $date)
                ->get(['id', 'violations']);
            foreach ($progressRecords as $progress) {
                if ($progress->violations && is_array($progress->violations)) {
                    $violationsCount += count($progress->violations);
                }
            }

            $seekAttempts = (int) LessonWatchSession::where('user_id', $userId)
                ->whereDate('started_at', $date)
                ->sum('seek_attempts');

            $maxPlaybackRate = (float) LessonWatchSession::where('user_id', $userId)
                ->whereDate('started_at', $date)
                ->max('max_playback_rate') ?? 1.0;

            // Last activity from activity_events
            $lastActivityAt = DB::table('activity_events')
                ->where('user_id', $userId)
                ->whereDate('occurred_at', $date)
                ->max('occurred_at');

            // Active seconds: approximate from watch sessions (sum of session durations)
            // Reuse $sessions loaded above
            $activeSeconds = 0;
            foreach ($sessions as $session) {
                if ($session->started_at && $session->ended_at) {
                    $activeSeconds += $session->started_at->diffInSeconds($session->ended_at);
                } elseif ($session->started_at) {
                    // If session not ended, use watched_seconds as approximation
                    $activeSeconds += $session->watched_seconds;
                }
            }

            // Stagnation score heuristic
            $stagnationScore = 0;
            if ($watchedSeconds == 0) {
                $stagnationScore += 2;
            }
            if ($taskCheckins == 0) {
                // Check if user has active tasks
                $hasActiveTask = DB::table('task_progress')
                    ->where('user_id', $userId)
                    ->where('status', '!=', 'completed')
                    ->exists();
                if ($hasActiveTask) {
                    $stagnationScore += 2;
                }
            }
            // Check last activity threshold (if last activity older than 3 days, add score)
            if ($lastActivityAt) {
                $daysSinceActivity = Carbon::parse($lastActivityAt)->diffInDays(now());
                if ($daysSinceActivity > 3) {
                    $stagnationScore += 3;
                }
            } else {
                // No activity at all
                $stagnationScore += 3;
            }

            // Upsert daily metric
            DailyUserMetric::updateOrCreate(
                [
                    'user_id' => $userId,
                    'date' => $date,
                ],
                [
                    'active_seconds' => $activeSeconds,
                    'watched_seconds' => $watchedSeconds,
                    'lessons_completed' => $lessonsCompleted,
                    'reflections_submitted' => $reflectionsSubmitted,
                    'task_checkins' => $taskCheckins,
                    'violations_count' => $violationsCount,
                    'seek_attempts' => $seekAttempts,
                    'max_playback_rate' => $maxPlaybackRate,
                    'stagnation_score' => $stagnationScore,
                    'last_activity_at' => $lastActivityAt ? Carbon::parse($lastActivityAt) : null,
                ]
            );
        }
    }
}

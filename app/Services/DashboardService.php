<?php

namespace App\Services;

use App\Models\User;
use App\Models\LessonVideoProgress;
use App\Models\LessonProgress;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get comprehensive dashboard data for a user.
     */
    public function getDashboardData(User $user): array
    {
        // Get all enrolled courses with lessons (eager loaded, properly ordered)
        $enrollments = $user->enrollments()
            ->with([
                'course' => function($query) {
                    $query->with([
                        'modules' => function($query) {
                            $query->orderBy('sort_order')->orderBy('id');
                        },
                        'modules.lessons' => function($query) {
                            $query->orderBy('sort_order')->orderBy('id');
                        }
                    ]);
                }
            ])
            ->get();

        // Get all lesson IDs from enrolled courses
        $allLessonIds = $enrollments->flatMap(function($enrollment) {
            return $enrollment->course->modules->flatMap->lessons->pluck('id');
        })->unique();

        // Calculate KPI stats efficiently
        $stats = $this->calculateStats($user, $allLessonIds);

        // Get continue watching (in-progress lessons 1-94%)
        $continueWatching = $this->getContinueWatching($user, $allLessonIds);

        // Get watch time data
        $watchTime = $this->getWatchTime($user);

        // Get streak data
        $streak = $this->getStreakData($user);

        // Get recent notes
        $recentNotes = $this->getRecentNotes($user);

        // Get continue learning (next lesson in course)
        $continueLearning = $this->getContinueLearning($user, $enrollments);

        return [
            'stats' => $stats,
            'continue_watching' => $continueWatching,
            'watch_time' => $watchTime,
            'streak' => $streak,
            'recent_notes' => $recentNotes,
            'continue_learning' => $continueLearning,
        ];
    }

    /**
     * Calculate KPI statistics.
     */
    private function calculateStats(User $user, $allLessonIds): array
    {
        // Watched lessons (completed)
        $watchedLessons = $user->lessonProgress()
            ->whereIn('lesson_id', $allLessonIds)
            ->whereNotNull('completed_at')
            ->count();

        // Total lessons
        $totalLessons = $allLessonIds->count();

        // Remaining lessons
        $remainingLessons = max(0, $totalLessons - $watchedLessons);

        // Total watch time (sum of watched_seconds from lesson_progress)
        $totalWatchTime = $user->lessonProgress()
            ->whereIn('lesson_id', $allLessonIds)
            ->sum('watched_seconds') ?? 0;

        // Current streak (days with watch activity)
        $currentStreak = $this->calculateWatchStreak($user);

        return [
            'watched_lessons' => $watchedLessons,
            'remaining_lessons' => $remainingLessons,
            'total_lessons' => $totalLessons,
            'total_watch_time_seconds' => $totalWatchTime,
            'total_watch_time_formatted' => $this->formatWatchTime($totalWatchTime),
            'current_streak' => $currentStreak,
            'courses_enrolled' => $user->enrollments()->count(),
            'total_points' => \App\Services\PointsService::getTotalPoints($user),
        ];
    }

    /**
     * Get continue watching lessons (in-progress, 1-94%).
     */
    private function getContinueWatching(User $user, $allLessonIds): array
    {
        $inProgressLessons = LessonVideoProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $allLessonIds)
            ->where('is_completed', false)
            ->where('percent_complete', '>=', 1)
            ->where('percent_complete', '<', 95)
            ->with(['lesson.module.course'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(function($progress) {
                $lesson = $progress->lesson;
                if (!$lesson) return null;

                return [
                    'lesson_id' => $lesson->id,
                    'lesson_title' => $lesson->title,
                    'course_id' => $lesson->module->course_id,
                    'course_title' => $lesson->module->course->title,
                    'percent_complete' => $progress->percent_complete,
                    'last_position_seconds' => $progress->last_position_seconds,
                    'duration_seconds' => $progress->duration_seconds,
                    'last_watched_at' => $progress->updated_at->diffForHumans(),
                    'last_watched_at_raw' => $progress->updated_at->toIso8601String(),
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        return $inProgressLessons;
    }

    /**
     * Get watch time statistics.
     */
    private function getWatchTime(User $user): array
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();

        // Watch time today (sum of watched_seconds from lesson_progress updated today)
        // This is more accurate than video progress as it tracks actual watch time
        $watchTimeToday = LessonProgress::where('user_id', $user->id)
            ->whereDate('updated_at', $today)
            ->sum('watched_seconds') ?? 0;

        // Watch time this week
        $watchTimeThisWeek = LessonProgress::where('user_id', $user->id)
            ->where('updated_at', '>=', $weekStart)
            ->sum('watched_seconds') ?? 0;

        // Daily goal (default: 30 minutes = 1800 seconds)
        $dailyGoalSeconds = 1800; // 30 minutes
        $dailyGoalMinutes = 30;

        // Progress towards daily goal
        $dailyGoalProgress = $dailyGoalSeconds > 0 
            ? min(100, ($watchTimeToday / $dailyGoalSeconds) * 100) 
            : 0;

        return [
            'today_seconds' => $watchTimeToday,
            'today_formatted' => $this->formatWatchTime($watchTimeToday),
            'today_minutes' => round($watchTimeToday / 60, 1),
            'this_week_seconds' => $watchTimeThisWeek,
            'this_week_formatted' => $this->formatWatchTime($watchTimeThisWeek),
            'daily_goal_seconds' => $dailyGoalSeconds,
            'daily_goal_minutes' => $dailyGoalMinutes,
            'daily_goal_progress' => round($dailyGoalProgress, 1),
        ];
    }

    /**
     * Get streak data.
     */
    private function getStreakData(User $user): array
    {
        $streak = $this->calculateWatchStreak($user);

        // Determine streak badge
        $badge = null;
        if ($streak >= 30) {
            $badge = ['level' => 'master', 'label' => '30+ Days', 'color' => 'secondary'];
        } elseif ($streak >= 14) {
            $badge = ['level' => 'expert', 'label' => '14+ Days', 'color' => 'primary'];
        } elseif ($streak >= 7) {
            $badge = ['level' => 'dedicated', 'label' => '7+ Days', 'color' => 'primary'];
        } elseif ($streak >= 3) {
            $badge = ['level' => 'committed', 'label' => '3+ Days', 'color' => 'primary'];
        }

        return [
            'days' => $streak,
            'badge' => $badge,
        ];
    }

    /**
     * Calculate watch streak (consecutive days with video watch activity).
     */
    private function calculateWatchStreak(User $user): int
    {
        // Get distinct dates where user watched videos (from video progress or lesson progress)
        $watchDates = LessonVideoProgress::where('user_id', $user->id)
            ->where('updated_at', '>=', now()->subDays(60)) // Check last 60 days
            ->selectRaw('DATE(updated_at) as watch_date')
            ->distinct()
            ->orderByDesc('watch_date')
            ->pluck('watch_date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->values()
            ->toArray();

        if (empty($watchDates)) {
            return 0;
        }

        $streak = 0;
        $currentDate = Carbon::today();
        $expectedDate = $currentDate->copy();

        foreach ($watchDates as $watchDate) {
            $date = Carbon::parse($watchDate);
            
            // Check if this date matches the expected date (today, yesterday, etc.)
            if ($date->isSameDay($expectedDate)) {
                $streak++;
                $expectedDate->subDay(); // Next expected date is one day earlier
            } elseif ($date->isBefore($expectedDate)) {
                // Gap found - streak broken
                break;
            }
            // If date is after expected, skip it (shouldn't happen with DESC order, but safety check)
        }

        return $streak;
    }

    /**
     * Get recent notes.
     */
    private function getRecentNotes(User $user, int $limit = 5): array
    {
        return Note::where('user_id', $user->id)
            ->with(['lesson.module.course', 'course'])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(function($note) {
                $related = null;
                $courseId = null;
                $lessonId = null;

                if ($note->lesson) {
                    $related = $note->lesson->title;
                    $courseId = $note->lesson->module->course_id;
                    $lessonId = $note->lesson->id;
                } elseif ($note->course) {
                    $related = $note->course->title;
                    $courseId = $note->course->id;
                }

                return [
                    'id' => $note->id,
                    'title' => $note->title ?? 'Untitled Note',
                    'content' => $note->content,
                    'preview' => substr($note->content, 0, 100) . (strlen($note->content) > 100 ? '...' : ''),
                    'scope' => $note->scope,
                    'pinned' => $note->pinned,
                    'updated_at' => $note->updated_at->diffForHumans(),
                    'updated_at_raw' => $note->updated_at->toIso8601String(),
                    'related' => $related,
                    'course_id' => $courseId,
                    'lesson_id' => $lessonId,
                ];
            })
            ->toArray();
    }

    /**
     * Get continue learning (next lesson in course).
     */
    private function getContinueLearning(User $user, $enrollments): ?array
    {
        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            $totalLessons = $course->modules->flatMap->lessons->count();

            if ($totalLessons === 0) continue;

            $completedLessons = $user->lessonProgress()
                ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
                ->whereNotNull('completed_at')
                ->count();

            $progress = round(($completedLessons / $totalLessons) * 100);

            if ($progress < 100) {
                // Find next incomplete lesson
                $nextLesson = $course->modules->flatMap->lessons
                    ->first(function($lesson) use ($user) {
                        return !$user->lessonProgress()
                            ->where('lesson_id', $lesson->id)
                            ->whereNotNull('completed_at')
                            ->exists();
                    });

                // Get video progress for resume functionality
                $videoProgress = null;
                if ($nextLesson) {
                    $videoProgressRecord = LessonVideoProgress::forUserAndLesson($user->id, $nextLesson->id);
                    if ($videoProgressRecord && !$videoProgressRecord->is_completed) {
                        $videoProgress = [
                            'last_position_seconds' => $videoProgressRecord->last_position_seconds,
                            'percent_complete' => $videoProgressRecord->percent_complete,
                            'duration_seconds' => $videoProgressRecord->duration_seconds,
                        ];
                    }
                }

                return [
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'lesson_id' => $nextLesson ? $nextLesson->id : null,
                    'lesson_title' => $nextLesson ? $nextLesson->title : 'Start first lesson',
                    'progress' => $progress,
                    'image' => $course->thumbnail ?? 'https://ui-avatars.com/api/?name=' . urlencode(substr($course->title, 0, 2)) . '&background=059669&color=fff&size=400',
                    'video_progress' => $videoProgress,
                ];
            }
        }

        return null;
    }

    /**
     * Format watch time in human-readable format.
     */
    private function formatWatchTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }
}

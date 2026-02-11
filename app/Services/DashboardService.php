<?php

namespace App\Services;

use App\Models\User;
use App\Models\LessonVideoProgress;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\LessonQuizAttempt;
use App\Models\TaskProgress;
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

        // Get remaining quizzes (completed lessons with quiz not yet taken)
        $remainingQuizzesList = $this->getRemainingQuizzesList($user, $enrollments);

        // Get simple course milestone summaries
        $courseMilestones = $this->getCourseMilestones($user, $enrollments);

        return [
            'stats' => $stats,
            'continue_watching' => $continueWatching,
            'watch_time' => $watchTime,
            'streak' => $streak,
            'recent_notes' => $recentNotes,
            'continue_learning' => $continueLearning,
            'remaining_quizzes_list' => $remainingQuizzesList,
            'course_milestones' => $courseMilestones,
        ];
    }

    /**
     * Get list of lessons that have a quiz and are completed by user but quiz not yet taken.
     */
    private function getRemainingQuizzesList(User $user, $enrollments): array
    {
        $allLessonIds = $enrollments->flatMap(function($enrollment) {
            return $enrollment->course->modules->flatMap->lessons->pluck('id');
        })->unique();

        $completedLessonIds = $user->lessonProgress()
            ->whereIn('lesson_id', $allLessonIds)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id');

        $attemptedQuizLessonIds = $user->lessonQuizAttempts()
            ->whereIn('lesson_id', $allLessonIds)
            ->pluck('lesson_id');

        $remaining = $completedLessonIds->diff($attemptedQuizLessonIds);
        if ($remaining->isEmpty()) {
            return [];
        }

        $lessons = \App\Models\Lesson::with('module.course')
            ->whereIn('id', $remaining)
            ->whereHas('quizQuestions')
            ->orderBy('sort_order')
            ->limit(10)
            ->get();

        return $lessons->map(function($lesson) {
            return [
                'lesson_id' => $lesson->id,
                'lesson_title' => $lesson->title,
                'course_id' => $lesson->module->course_id,
                'course_title' => $lesson->module->course->title,
            ];
        })->toArray();
    }

    /**
     * Get milestone-style summaries for each enrolled course.
     *
     * This does not persist badges; it just surfaces simple progress bands:
     * - <25%: "just_started"
     * - 25-49%: "making_progress"
     * - 50-74%: "halfway_there"
     * - 75-99%: "almost_finished"
     * - 100%: "completed"
     */
    private function getCourseMilestones(User $user, $enrollments): array
    {
        $milestones = [];

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            if (! $course) {
                continue;
            }

            $lessonIds = $course->modules->flatMap->lessons->pluck('id');
            $totalLessons = $lessonIds->count();

            if ($totalLessons === 0) {
                continue;
            }

            $completedLessons = $user->lessonProgress()
                ->whereIn('lesson_id', $lessonIds)
                ->whereNotNull('completed_at')
                ->count();

            $progress = round(($completedLessons / $totalLessons) * 100);

            if ($progress === 0) {
                continue;
            }

            if ($progress >= 100) {
                $level = 'completed';
                $label = 'Completed';
            } elseif ($progress >= 75) {
                $level = 'almost_finished';
                $label = 'Almost finished';
            } elseif ($progress >= 50) {
                $level = 'halfway_there';
                $label = 'Halfway there';
            } elseif ($progress >= 25) {
                $level = 'making_progress';
                $label = 'Making progress';
            } else {
                $level = 'just_started';
                $label = 'Just started';
            }

            $milestones[] = [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'progress' => $progress,
                'level' => $level,
                'label' => $label,
            ];
        }

        // Return up to 3 courses, highest progress first
        usort($milestones, fn($a, $b) => $b['progress'] <=> $a['progress']);

        return array_slice($milestones, 0, 3);
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

        // Quiz stats: lessons with quiz that user has completed (video) vs quiz attempted
        $lessonIdsWithQuiz = \App\Models\Lesson::whereIn('id', $allLessonIds->toArray())
            ->whereHas('quizQuestions')
            ->pluck('id');
        $completedLessonIdsSet = $user->lessonProgress()
            ->whereIn('lesson_id', $allLessonIds)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id');
        $lessonsCompletedWithQuiz = $completedLessonIdsSet->intersect($lessonIdsWithQuiz)->values();
        $quizAttemptLessonIds = $user->lessonQuizAttempts()
            ->whereIn('lesson_id', $allLessonIds)
            ->pluck('lesson_id');
        $completedQuizzes = $quizAttemptLessonIds->count();
        $remainingQuizzes = $lessonsCompletedWithQuiz->diff($quizAttemptLessonIds)->count();

        return [
            'watched_lessons' => $watchedLessons,
            'remaining_lessons' => $remainingLessons,
            'total_lessons' => $totalLessons,
            'total_watch_time_seconds' => $totalWatchTime,
            'total_watch_time_formatted' => $this->formatWatchTime($totalWatchTime),
            'current_streak' => $currentStreak,
            'courses_enrolled' => $user->enrollments()->count(),
            'total_points' => \App\Services\PointsService::getTotalPoints($user),
            'completed_quizzes' => $completedQuizzes,
            'remaining_quizzes' => $remainingQuizzes,
            'total_quizzes_available' => $lessonIdsWithQuiz->count(),
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
     * Calculate streak (consecutive days with meaningful learning activity).
     *
     * Meaningful activity includes:
     * - Watching any lesson video (LessonVideoProgress)
     * - Submitting a reflection (LessonReflection)
     * - Completing a quiz (LessonQuizAttempt)
     * - Checking in to a practice task (TaskProgress last_checkin_on)
     */
    private function calculateWatchStreak(User $user): int
    {
        $since = now()->subDays(60);

        $dates = collect();

        // 1) Video watch activity
        $videoDates = LessonVideoProgress::where('user_id', $user->id)
            ->where('updated_at', '>=', $since)
            ->selectRaw('DATE(updated_at) as d')
            ->distinct()
            ->pluck('d');

        // 2) Reflections submitted
        $reflectionDates = LessonReflection::where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', $since)
            ->selectRaw('DATE(submitted_at) as d')
            ->distinct()
            ->pluck('d');

        // 3) Quizzes completed
        $quizDates = LessonQuizAttempt::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since)
            ->selectRaw('DATE(completed_at) as d')
            ->distinct()
            ->pluck('d');

        // 4) Task check-ins (last_checkin_on)
        $taskDates = TaskProgress::where('user_id', $user->id)
            ->whereNotNull('last_checkin_on')
            ->where('last_checkin_on', '>=', $since->copy()->startOfDay())
            ->selectRaw('DATE(last_checkin_on) as d')
            ->distinct()
            ->pluck('d');

        $dates = $dates
            ->merge($videoDates)
            ->merge($reflectionDates)
            ->merge($quizDates)
            ->merge($taskDates)
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->sortDesc()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = Carbon::today();
        $expectedDate = $currentDate->copy();

        foreach ($dates as $day) {
            $date = Carbon::parse($day);
            
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

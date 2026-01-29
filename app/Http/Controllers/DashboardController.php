<?php

namespace App\Http\Controllers;

use App\Services\PointsService;
use App\Models\Note;
use App\Models\Discussion;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the student dashboard with real data.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get enrolled courses with progress
        $enrollments = $user->enrollments()
            ->with('course.modules.lessons')
            ->get();

        // Calculate stats
        $stats = [
            'courses_enrolled' => $enrollments->count(),
            'lessons_watched' => $user->lessonProgress()->whereNotNull('completed_at')->count(),
            'current_streak' => $this->getCurrentStreak($user),
            'total_points' => PointsService::getTotalPoints($user),
        ];

        // Get recent activity (last 10 activities from various sources)
        $recentActivity = collect();

        // Completed lessons
        $completedLessons = $user->lessonProgress()
            ->with('lesson.module.course')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit(5)
            ->get()
            ->map(function($progress) {
                return [
                    'id' => $progress->id,
                    'type' => 'lesson_completed',
                    'title' => $progress->lesson->title,
                    'course' => $progress->lesson->module->course->title,
                    'course_id' => $progress->lesson->module->course->id,
                    'lesson_id' => $progress->lesson->id,
                    'created_at' => $progress->completed_at,
                    'time' => $progress->completed_at->diffForHumans()
                ];
            });

        $recentActivity = $recentActivity->merge($completedLessons);

        // Latest notes
        $latestNotes = Note::where('user_id', $user->id)
            ->with(['lesson.module.course', 'course'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($note) {
                $related = null;
                $courseId = null;
                $lessonId = null;

                if ($note->lesson) {
                    $related = $note->lesson->title;
                    $courseId = $note->lesson->module->course->id;
                    $lessonId = $note->lesson->id;
                } elseif ($note->course) {
                    $related = $note->course->title;
                    $courseId = $note->course->id;
                }

                return [
                    'id' => $note->id,
                    'type' => 'note_created',
                    'title' => $note->title,
                    'related' => $related,
                    'course_id' => $courseId,
                    'lesson_id' => $lessonId,
                    'created_at' => $note->created_at,
                    'time' => $note->created_at->diffForHumans()
                ];
            });

        $recentActivity = $recentActivity->merge($latestNotes);

        // Community posts (discussions)
        $communityPosts = Discussion::where('user_id', $user->id)
            ->with(['course', 'lesson.module.course'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($discussion) {
                $courseId = null;
                $lessonId = null;
                $related = null;

                if ($discussion->course) {
                    $courseId = $discussion->course->id;
                    $related = $discussion->course->title;
                } elseif ($discussion->lesson) {
                    $courseId = $discussion->lesson->module->course->id;
                    $lessonId = $discussion->lesson->id;
                    $related = $discussion->lesson->title;
                }

                return [
                    'id' => $discussion->id,
                    'type' => 'discussion_created',
                    'title' => $discussion->title,
                    'related' => $related,
                    'course_id' => $courseId,
                    'lesson_id' => $lessonId,
                    'created_at' => $discussion->created_at,
                    'time' => $discussion->created_at->diffForHumans()
                ];
            });

        $recentActivity = $recentActivity->merge($communityPosts);

        // Sort by created_at and limit to 10 most recent
        $recentActivity = $recentActivity->sortByDesc('created_at')->take(10)->values();

        // Get latest notes for display
        $latestNotesDisplay = Note::where('user_id', $user->id)
            ->with(['lesson.module.course', 'course'])
            ->latest()
            ->limit(3)
            ->get()
            ->map(function($note) {
                $related = null;
                if ($note->lesson) {
                    $related = $note->lesson->title;
                } elseif ($note->course) {
                    $related = $note->course->title;
                }

                return [
                    'id' => $note->id,
                    'title' => $note->title,
                    'preview' => substr($note->content, 0, 100) . (strlen($note->content) > 100 ? '...' : ''),
                    'related' => $related,
                    'scope' => $note->scope,
                    'created_at' => $note->created_at->diffForHumans()
                ];
            });

        // Get latest community posts
        $latestCommunityPosts = Discussion::with(['user', 'course', 'lesson.module.course', 'replies'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($discussion) {
                $related = null;
                if ($discussion->course) {
                    $related = $discussion->course->title;
                } elseif ($discussion->lesson) {
                    $related = $discussion->lesson->title;
                }

                return [
                    'id' => $discussion->id,
                    'title' => $discussion->title,
                    'body' => substr($discussion->body, 0, 150) . (strlen($discussion->body) > 150 ? '...' : ''),
                    'author' => $discussion->user->name,
                    'related' => $related,
                    'replies_count' => $discussion->replies->count(),
                    'created_at' => $discussion->created_at->diffForHumans()
                ];
            });

        // Get continue learning (most recent incomplete course with next lesson)
        $continueLearning = null;
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

                // If no incomplete lesson found, check for in-progress lessons
                if (!$nextLesson) {
                    $inProgressLesson = $user->lessonProgress()
                        ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
                        ->whereNull('completed_at')
                        ->latest('updated_at')
                        ->first();

                    if ($inProgressLesson) {
                        $nextLesson = $course->modules->flatMap->lessons
                            ->firstWhere('id', $inProgressLesson->lesson_id);
                    }
                }

                $continueLearning = [
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'lesson_id' => $nextLesson ? $nextLesson->id : null,
                    'lesson_title' => $nextLesson ? $nextLesson->title : 'Start first lesson',
                    'progress' => $progress,
                    'image' => $course->thumbnail ?? 'https://ui-avatars.com/api/?name=' . urlencode(substr($course->title, 0, 2)) . '&background=059669&color=fff&size=400',
                ];
                break;
            }
        }

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'continue_learning' => $continueLearning,
            'latest_notes' => $latestNotesDisplay,
            'latest_community_posts' => $latestCommunityPosts,
        ]);
    }

    /**
     * Calculate current habit streak for user.
     */
    private function getCurrentStreak($user)
    {
        $habitLogs = $user->habitLogs()
            ->whereDate('log_date', '>=', now()->subDays(30))
            ->orderBy('log_date', 'desc')
            ->get();

        if ($habitLogs->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = now()->startOfDay();

        foreach ($habitLogs->groupBy(fn($log) => $log->log_date) as $date => $logs) {
            if ($currentDate->format('Y-m-d') === $date || $currentDate->subDay()->format('Y-m-d') === $date) {
                $streak++;
                $currentDate = \Carbon\Carbon::parse($date)->startOfDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}

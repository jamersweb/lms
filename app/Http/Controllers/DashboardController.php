<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Models\Discussion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Display the student dashboard with real data.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get comprehensive dashboard data from service
        $dashboardData = $this->dashboardService->getDashboardData($user);

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
        $latestNotes = \App\Models\Note::where('user_id', $user->id)
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
                    $courseId = $note->lesson->module->course_id;
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

        return Inertia::render('Dashboard/Index', [
            'stats' => $dashboardData['stats'],
            'continue_watching' => $dashboardData['continue_watching'],
            'watch_time' => $dashboardData['watch_time'],
            'streak' => $dashboardData['streak'],
            'recent_notes' => $dashboardData['recent_notes'],
            'continue_learning' => $dashboardData['continue_learning'],
            'remaining_quizzes_list' => $dashboardData['remaining_quizzes_list'] ?? [],
            'course_milestones' => $dashboardData['course_milestones'] ?? [],
            'recent_activity' => $recentActivity,
            'latest_community_posts' => $latestCommunityPosts,
        ]);
    }
}

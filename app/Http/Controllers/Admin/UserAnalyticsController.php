<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseProgressSnapshot;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\LessonWatchSession;
use App\Models\Question;
use App\Models\TaskProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class UserAnalyticsController extends Controller
{
    public function show(Request $request, User $user)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        // Overview: Get latest course progress snapshot
        $latestSnapshot = CourseProgressSnapshot::where('user_id', $user->id)
            ->with(['course', 'nextLesson'])
            ->latest('updated_at')
            ->first();

        // Watch Sessions
        $watchSessionsQuery = LessonWatchSession::where('user_id', $user->id)
            ->with(['lesson.module.course'])
            ->orderBy('started_at', 'desc');

        if ($request->date_from) {
            $watchSessionsQuery->whereDate('started_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $watchSessionsQuery->whereDate('started_at', '<=', $request->date_to);
        }

        $watchSessions = $watchSessionsQuery->paginate(20, ['*'], 'watch_sessions_page');

        // Violations: Aggregate from watch sessions and progress
        $violations = [];
        $sessionsQuery = LessonWatchSession::where('user_id', $user->id)
            ->whereNotNull('violations');

        if ($request->date_from) {
            $sessionsQuery->whereDate('started_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $sessionsQuery->whereDate('started_at', '<=', $request->date_to);
        }

        $sessions = $sessionsQuery->get();
        foreach ($sessions as $session) {
            if ($session->violations && is_array($session->violations)) {
                foreach ($session->violations as $violation) {
                    $violations[] = [
                        'type' => $violation['type'] ?? 'unknown',
                        'at' => $violation['at'] ?? $session->started_at?->toDateTimeString(),
                        'lesson' => [
                            'id' => $session->lesson->id,
                            'title' => $session->lesson->title,
                        ],
                        'meta' => $violation['meta'] ?? [],
                    ];
                }
            }
        }

        $progressQuery = LessonProgress::where('user_id', $user->id)
            ->whereNotNull('violations')
            ->with('lesson');

        if ($request->date_from) {
            $progressQuery->whereDate('updated_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $progressQuery->whereDate('updated_at', '<=', $request->date_to);
        }

        $progressRecords = $progressQuery->get();
        foreach ($progressRecords as $progress) {
            if ($progress->violations && is_array($progress->violations)) {
                foreach ($progress->violations as $violation) {
                    $violations[] = [
                        'type' => $violation['type'] ?? 'unknown',
                        'at' => $violation['at'] ?? $progress->updated_at?->toDateTimeString(),
                        'lesson' => [
                            'id' => $progress->lesson->id,
                            'title' => $progress->lesson->title,
                        ],
                        'meta' => $violation['meta'] ?? [],
                    ];
                }
            }
        }

        // Reflections
        $reflectionsQuery = LessonReflection::where('user_id', $user->id)
            ->with(['lesson.module.course'])
            ->orderBy('created_at', 'desc');

        if ($request->date_from) {
            $reflectionsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $reflectionsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $reflections = $reflectionsQuery->paginate(20, ['*'], 'reflections_page');

        // Tasks
        $tasksQuery = TaskProgress::where('user_id', $user->id)
            ->with(['task.taskable'])
            ->orderBy('created_at', 'desc');

        if ($request->date_from) {
            $tasksQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $tasksQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $tasks = $tasksQuery->paginate(20, ['*'], 'tasks_page');

        // Questions
        $questionsQuery = Question::where('user_id', $user->id)
            ->with(['assignee'])
            ->orderBy('created_at', 'desc');

        if ($request->date_from) {
            $questionsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $questionsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $questions = $questionsQuery->paginate(20, ['*'], 'questions_page');

        return Inertia::render('Admin/Analytics/UserShow', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'last_active_at' => $user->last_active_at?->diffForHumans(),
            ],
            'overview' => $latestSnapshot ? [
                'course' => [
                    'id' => $latestSnapshot->course->id,
                    'title' => $latestSnapshot->course->title,
                ],
                'lessons_completed' => $latestSnapshot->lessons_completed,
                'lessons_total' => $latestSnapshot->lessons_total,
                'progress_percentage' => $latestSnapshot->lessons_total > 0
                    ? round(($latestSnapshot->lessons_completed / $latestSnapshot->lessons_total) * 100)
                    : 0,
                'next_lesson' => $latestSnapshot->nextLesson ? [
                    'id' => $latestSnapshot->nextLesson->id,
                    'title' => $latestSnapshot->nextLesson->title,
                ] : null,
                'next_lesson_release_at' => $latestSnapshot->next_lesson_release_at?->toDateTimeString(),
                'blocked_by' => $latestSnapshot->blocked_by,
            ] : null,
            'watch_sessions' => $watchSessions,
            'violations' => $violations,
            'reflections' => $reflections,
            'tasks' => $tasks,
            'questions' => $questions,
            'filters' => [
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ],
        ]);
    }
}

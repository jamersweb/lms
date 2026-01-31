<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseProgressSnapshot;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CourseMasteryController extends Controller
{
    public function show(Request $request, Course $course)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $course->load(['modules.lessons']);

        // Get all snapshots for this course
        $snapshots = CourseProgressSnapshot::where('course_id', $course->id)
            ->with(['user', 'nextLesson'])
            ->get();

        $totalEnrolled = $snapshots->count();

        // Calculate completion distribution
        $distribution = [
            '0-25' => 0,
            '25-50' => 0,
            '50-75' => 0,
            '75-100' => 0,
            '100' => 0,
        ];

        foreach ($snapshots as $snapshot) {
            if ($snapshot->lessons_total == 0) {
                continue;
            }
            $percentage = ($snapshot->lessons_completed / $snapshot->lessons_total) * 100;
            if ($percentage == 100) {
                $distribution['100']++;
            } elseif ($percentage >= 75) {
                $distribution['75-100']++;
            } elseif ($percentage >= 50) {
                $distribution['50-75']++;
            } elseif ($percentage >= 25) {
                $distribution['25-50']++;
            } else {
                $distribution['0-25']++;
            }
        }

        // Get student list with progress details
        $students = $snapshots->map(function (CourseProgressSnapshot $snapshot) {
            return [
                'id' => $snapshot->user->id,
                'name' => $snapshot->user->name,
                'email' => $snapshot->user->email,
                'lessons_completed' => $snapshot->lessons_completed,
                'lessons_total' => $snapshot->lessons_total,
                'progress_percentage' => $snapshot->lessons_total > 0
                    ? round(($snapshot->lessons_completed / $snapshot->lessons_total) * 100)
                    : 0,
                'reflections_done' => $snapshot->reflections_done,
                'reflections_required' => $snapshot->reflections_required,
                'tasks_done' => $snapshot->tasks_done,
                'tasks_required' => $snapshot->tasks_required,
                'next_lesson' => $snapshot->nextLesson ? [
                    'id' => $snapshot->nextLesson->id,
                    'title' => $snapshot->nextLesson->title,
                ] : null,
                'next_lesson_release_at' => $snapshot->next_lesson_release_at?->diffForHumans(),
                'blocked_by' => $snapshot->blocked_by,
            ];
        })->sortByDesc('progress_percentage')->values();

        return Inertia::render('Admin/Analytics/CourseMastery', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'total_enrolled' => $totalEnrolled,
            'distribution' => $distribution,
            'students' => $students,
        ]);
    }
}

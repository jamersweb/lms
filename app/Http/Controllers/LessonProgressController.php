<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\PointsService;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    /**
     * Mark a lesson as complete.
     */
    public function complete(Request $request, Lesson $lesson)
    {
        $user = auth()->user();
        
        // Check if user is enrolled in the course
        $course = $lesson->module->course;
        if (!$user->isEnrolledIn($course->id)) {
            abort(403, 'You must be enrolled in this course to mark lessons as complete.');
        }
        
        // Check if already completed
        $existingProgress = $user->lessonProgress()
            ->where('lesson_id', $lesson->id)
            ->whereNotNull('completed_at')
            ->first();
        
        if ($existingProgress) {
            return back()->with('info', 'Lesson already marked as complete.');
        }
        
        // Create or update progress
        $progress = $user->lessonProgress()->updateOrCreate(
            ['lesson_id' => $lesson->id],
            [
                'is_completed' => true,
                'completed_at' => now()
            ]
        );
        
        // Award points
        PointsService::award($user, 'lesson_completed', 10);
        
        // Check if course is completed
        $totalLessons = $course->modules->flatMap->lessons->count();
        $completedLessons = $user->lessonProgress()
            ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
            ->whereNotNull('completed_at')
            ->count();
        
        if ($totalLessons === $completedLessons) {
            PointsService::award($user, 'course_completed', 50);
            return back()->with('success', 'Congratulations! You completed the course!');
        }
        
        return back()->with('success', 'Lesson marked as complete! +10 points');
    }
}

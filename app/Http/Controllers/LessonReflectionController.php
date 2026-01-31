<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonReflection;
use App\Models\LessonProgress;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class LessonReflectionController extends Controller
{
    /**
     * Store or update a lesson reflection (Spiritual Takeaway).
     * 
     * Requires:
     * - User must have validly completed the lesson video
     * - Reflection must be at least 30 characters
     */
    public function store(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $course = $lesson->module->course;

        // Check enrollment
        if (! $user->isEnrolledIn($course->id) && ! $lesson->is_free_preview) {
            abort(403, 'You must be enrolled in this course to submit reflections.');
        }

        // Check if lesson is completed
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if (!$progress || !$progress->completed_at) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Complete the lesson video first.',
                ], 422);
            }
            
            return back()
                ->withErrors(['reflection' => 'Complete the lesson video first.'])
                ->with('error', 'Complete the lesson video first.');
        }

        // Validate reflection content
        $data = $request->validate([
            'takeaway' => ['required', 'string', 'min:30', 'max:5000'],
        ]);

        // Upsert reflection
        $reflection = LessonReflection::updateOrCreate(
            [
                'lesson_id' => $lesson->id,
                'user_id' => $user->id,
            ],
            [
                'takeaway' => $data['takeaway'],
                'submitted_at' => now(),
                'review_status' => 'pending',
            ]
        );

        // Log reflection submission
        $this->activityLogger->log(
            \App\Models\ActivityEvent::TYPE_LESSON_REFLECTION_SUBMITTED,
            $user,
            [
                'subject' => $lesson,
                'course_id' => $course->id,
                'module_id' => $lesson->module_id,
                'lesson_id' => $lesson->id,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Reflection saved successfully.',
            ]);
        }

        return back()->with('success', 'Reflection saved successfully.');
    }

    /**
     * Get the current user's reflection for a lesson (optional endpoint).
     */
    public function show(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        
        $reflection = LessonReflection::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($request->expectsJson()) {
            return response()->json([
                'reflection' => $reflection,
            ]);
        }

        return back();
    }
}


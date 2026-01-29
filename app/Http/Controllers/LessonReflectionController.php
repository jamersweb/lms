<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonReflection;
use Illuminate\Http\Request;

class LessonReflectionController extends Controller
{
    public function store(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        $course = $lesson->module->course;

        if (! $user->isEnrolledIn($course->id) && ! $lesson->is_free_preview) {
            abort(403);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $reflection = LessonReflection::updateOrCreate(
            [
                'lesson_id' => $lesson->id,
                'user_id' => $user->id,
            ],
            [
                'content' => $data['content'],
                'submitted_at' => now(),
                'review_status' => 'pending',
            ]
        );

        return back()->with('success', 'Reflection submitted for review.');
    }
}


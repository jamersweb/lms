<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonQuizQuestion;
use Illuminate\Http\Request;

class LessonQuizController extends Controller
{
    /**
     * Replace all quiz questions for a lesson.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'questions' => ['required', 'array'],
            'questions.*.question_text' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*' => ['required', 'string', 'max:500'],
            'questions.*.correct_indices' => ['array'],
            'questions.*.correct_indices.*' => ['integer', 'min:0'],
        ]);

        foreach ($validated['questions'] as $index => $q) {
            $maxIdx = count($q['options']) - 1;
            $indices = array_values(array_unique(array_map('intval', $q['correct_indices'] ?? [])));
            foreach ($indices as $idx) {
                if ($idx > $maxIdx) {
                    return back()->withErrors([
                        'questions.' . $index . '.correct_indices' => 'Each correct index must be between 0 and ' . $maxIdx . '.',
                    ]);
                }
            }
        }

        $lesson->quizQuestions()->delete();
        foreach ($validated['questions'] as $sortOrder => $q) {
            $correctIndices = array_values(array_unique(array_map('intval', $q['correct_indices'] ?? [])));
            $lesson->quizQuestions()->create([
                'question_text' => $q['question_text'],
                'options' => $q['options'],
                'correct_index' => $correctIndices[0] ?? 0,
                'correct_indices' => $correctIndices,
                'sort_order' => $sortOrder,
            ]);
        }

        return back()->with('success', 'Quiz questions saved.');
    }
}

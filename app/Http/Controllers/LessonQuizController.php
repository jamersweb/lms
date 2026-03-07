<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonQuizAttempt;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;

class LessonQuizController extends Controller
{
    /**
     * Submit quiz answers for a lesson. User must have completed the lesson.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $authorizationService = app(AuthorizationService::class);
        if (!$authorizationService->hasLessonAccess($user, $lesson)) {
            abort(403);
        }

        // Must have completed the lesson to take the quiz
        $progress = $user->lessonProgress()->where('lesson_id', $lesson->id)->first();
        if (!$progress || !$progress->completed_at) {
            return response()->json([
                'message' => 'Complete the lesson video first before taking the quiz.',
            ], 422);
        }

        $questions = $lesson->quizQuestions()->orderBy('sort_order')->get();
        if ($questions->isEmpty()) {
            return response()->json([
                'message' => 'This lesson has no quiz.',
            ], 422);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['array'],
            'answers.*.*' => ['integer', 'min:0'],
        ]);

        $answers = $validated['answers'];
        $correct = 0;
        foreach ($questions as $index => $question) {
            $userSelected = isset($answers[$question->id])
                ? array_values(array_unique(array_map('intval', (array) $answers[$question->id])))
                : [];
            $correctSet = $question->getCorrectIndices();
            sort($userSelected);
            sort($correctSet);
            if ($userSelected === $correctSet) {
                $correct++;
            }
        }

        $total = $questions->count();
        $passed = $total > 0 && $correct >= ceil($total * 0.6); // 60% to pass

        LessonQuizAttempt::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'score' => $correct,
                'total_questions' => $total,
                'passed' => $passed,
                'completed_at' => now(),
            ]
        );

        $percentage = $total > 0 ? round(($correct / $total) * 100) : 0;
        $triggerService = app(\App\Services\WhatsApp\TriggerService::class);
        if ($passed) {
            $triggerService->fireAsync('quiz_passed', $user, ['score' => (string) $percentage]);
        } else {
            $triggerService->fireAsync('quiz_failed', $user);
        }

        return response()->json([
            'score' => $correct,
            'total_questions' => $total,
            'passed' => $passed,
            'percentage' => $percentage,
        ]);
    }
}

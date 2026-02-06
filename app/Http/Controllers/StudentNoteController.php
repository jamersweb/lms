<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudentNote;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;

class StudentNoteController extends Controller
{
    /**
     * Get or show student note for a lesson
     */
    public function show(Lesson $lesson)
    {
        $user = auth()->user();
        $authorizationService = app(AuthorizationService::class);

        // Standardized authorization check
        try {
            $authorizationService->ensureLessonAccess($user, $lesson);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return response()->json([
                'error' => 'You do not have access to this lesson.'
            ], 403);
        }

        $note = StudentNote::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if (!$note) {
            return response()->json([
                'content' => '',
                'updated_at' => null,
            ]);
        }

        return response()->json([
            'content' => $note->content,
            'updated_at' => $note->updated_at->toISOString(),
        ]);
    }

    /**
     * Store or update student note
     */
    public function store(Request $request, Lesson $lesson)
    {
        $user = auth()->user();
        $authorizationService = app(AuthorizationService::class);

        // Standardized authorization check
        try {
            $authorizationService->ensureLessonAccess($user, $lesson);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return response()->json([
                'error' => 'You do not have access to this lesson.'
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'nullable|string|max:50000', // 50KB max, nullable to allow empty notes
        ]);

        // Don't save if content is only whitespace (unless it's clearing an existing note)
        $existingNote = StudentNote::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if (!$existingNote && (!isset($validated['content']) || trim($validated['content'] ?? '') === '')) {
            return response()->json([
                'success' => true,
                'message' => 'Note not saved (empty content)',
            ]);
        }

        // Use updateOrCreate since we have unique constraint
        // Allow empty content to be saved (user might clear the note)
        $note = StudentNote::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'content' => $validated['content'] ?? '',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Note saved successfully',
            'note' => [
                'content' => $note->content,
                'updated_at' => $note->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Delete student note
     */
    public function destroy(Lesson $lesson)
    {
        $user = auth()->user();
        $authorizationService = app(AuthorizationService::class);

        // Standardized authorization check
        try {
            $authorizationService->ensureLessonAccess($user, $lesson);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return response()->json([
                'error' => 'You do not have access to this lesson.'
            ], 403);
        }

        $note = StudentNote::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($note) {
            $note->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully',
        ]);
    }
}

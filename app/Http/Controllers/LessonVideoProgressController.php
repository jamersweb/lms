<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonVideoProgress;
use App\Services\AuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonVideoProgressController extends Controller
{
    public function __construct(
        private AuthorizationService $authorizationService
    ) {}

    /**
     * Update video progress for a lesson.
     * POST /lessons/{lesson}/video-progress
     */
    public function update(Request $request, Lesson $lesson)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check authorization
        try {
            $this->authorizationService->ensureLessonAccess($user, $lesson);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $validated = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'last_position_seconds' => ['required', 'integer', 'min:0'],
            'percent_complete' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_completed' => ['sometimes', 'boolean'],
            'provider' => ['sometimes', 'string', 'in:html5,youtube,vimeo,other'],
        ]);

        // Use lesson_id from route parameter, not request body
        $lessonId = $lesson->id;

        // Clamp last_position_seconds <= duration_seconds
        $lastPosition = min($validated['last_position_seconds'], $validated['duration_seconds']);
        $percentComplete = min(100, max(0, $validated['percent_complete']));

        // Determine completion status
        $isCompleted = $validated['is_completed'] ?? false;
        if (!$isCompleted) {
            // Auto-complete if percent >= 95 OR video ended (last_position >= duration - 1 second)
            if ($percentComplete >= 95 || $lastPosition >= max(0, $validated['duration_seconds'] - 1)) {
                $isCompleted = true;
                $lastPosition = $validated['duration_seconds']; // Set to end
                $percentComplete = 100;
            }
        }

        // Get or create progress record
        $progress = LessonVideoProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
            ],
            [
                'course_id' => $lesson->module->course_id,
                'provider' => $validated['provider'] ?? $this->detectProvider($lesson),
                'duration_seconds' => $validated['duration_seconds'],
                'last_position_seconds' => $lastPosition,
                'percent_complete' => $percentComplete,
                'is_completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
            ]
        );

        if ($isCompleted) {
            \Illuminate\Support\Facades\Cache::forget('dashboard_data_' . $user->id);
        }

        // Also update LessonProgress.watched_seconds for display purposes
        // This ensures the progress bar and percentage show correctly
        if ($validated['duration_seconds'] > 0) {
            $lessonProgress = \App\Models\LessonProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lessonId,
                ],
                [
                    'is_completed' => false,
                    'watched_seconds' => 0,
                    'max_playback_rate' => 1.0,
                    'seek_attempts' => 0,
                    'violations' => [],
                ]
            );
            
            // Update watched_seconds based on last_position_seconds
            // This represents how much of the video the user has watched
            $lessonProgress->watched_seconds = $lastPosition;
            $lessonProgress->save();
        }

        // Reload to get fresh data
        $progress->refresh();

        return response()->json([
            'success' => true,
            'progress' => [
                'last_position_seconds' => $progress->last_position_seconds,
                'percent_complete' => $progress->percent_complete,
                'is_completed' => $progress->is_completed,
                'completed_at' => $progress->completed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get video progress for a lesson.
     * GET /lesson-progress/{lesson}
     */
    public function show(Lesson $lesson)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check authorization
        try {
            $this->authorizationService->ensureLessonAccess($user, $lesson);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $progress = LessonVideoProgress::forUserAndLesson($user->id, $lesson->id);

        if (!$progress) {
            return response()->json([
                'last_position_seconds' => 0,
                'percent_complete' => 0,
                'is_completed' => false,
            ]);
        }

        return response()->json([
            'last_position_seconds' => $progress->last_position_seconds,
            'percent_complete' => $progress->percent_complete,
            'is_completed' => $progress->is_completed,
            'completed_at' => $progress->completed_at?->toIso8601String(),
        ]);
    }

    /**
     * Detect video provider from lesson.
     */
    private function detectProvider(Lesson $lesson): string
    {
        return match($lesson->video_provider) {
            'youtube' => 'youtube',
            'vimeo' => 'vimeo',
            'mp4' => 'html5',
            default => 'other',
        };
    }
}

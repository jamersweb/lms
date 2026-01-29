<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\ContentGatingService;
use App\Services\JourneyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    /**
     * Store video duration when first known from the client.
     */
    public function storeDuration(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $course = $lesson->module->course;

        if (! $user || (! $user->isEnrolledIn($course->id) && ! $lesson->is_free_preview)) {
            abort(403);
        }

        if (! $lesson->video_duration_seconds) {
            $lesson->video_duration_seconds = $data['duration_seconds'];
            $lesson->save();
        }

        return response()->json([
            'video_duration_seconds' => $lesson->video_duration_seconds,
        ]);
    }

    public function show($courseId, $lessonId)
    {
        $course = Course::with(['modules.lessons'])->findOrFail($courseId);
        $lesson = Lesson::with(['module', 'reflections', 'transcriptSegments'])->findOrFail($lessonId);

        $user = Auth::user();
        if ($user && ! ContentGatingService::userCanAccessLesson($user, $lesson)) {
            return \Inertia\Inertia::render('Errors/ForbiddenContent', [
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                ],
                'lesson' => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                ],
            ])->toResponse(request())->setStatusCode(403);
        }

        $completedLessonIds = [];
        $statusByLessonId = [];

        $reflectionData = null;

        if ($user) {
            // Initialize / recompute journey for enrolled users
            if ($user->isEnrolledIn($course->id)) {
                JourneyService::ensureProgressRecords($user, $course);
            }

            $lessonIds = $course->modules->flatMap->lessons->pluck('id');
            if ($lessonIds->isNotEmpty()) {
                $progress = LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->whereIn('lesson_id', $lessonIds)
                    ->get();

                $completedLessonIds = $progress
                    ->whereNotNull('completed_at')
                    ->pluck('lesson_id')
                    ->all();

                    $statusByLessonId = $progress
                    ->pluck('status', 'lesson_id')
                    ->toArray();

                $userReflection = $lesson->reflections
                    ->where('user_id', $user->id)
                    ->first();

                if ($userReflection) {
                    $reflectionData = [
                        'content' => $userReflection->content,
                        'submitted_at' => $userReflection->submitted_at,
                        'review_status' => $userReflection->review_status,
                        'mentor_note' => $userReflection->mentor_note,
                    ];
                }
            }
        }

        // Build flat playlist from all modules
        $playlist = [];
        $prevLessonId = null;
        $nextLessonId = null;
        $foundCurrent = false;

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $l) {
                $isCurrent = $l->id == $lessonId;
                $status = $statusByLessonId[$l->id] ?? 'available';

                if (!$foundCurrent && !$isCurrent) {
                    $prevLessonId = $l->id;
                }

                if ($foundCurrent && !$nextLessonId) {
                    $nextLessonId = $l->id;
                }

                if ($isCurrent) {
                    $foundCurrent = true;
                }

                $playlist[] = [
                    'id' => $l->id,
                    'title' => $l->title,
                    'duration' => $this->formatDuration($l->duration_seconds),
                    'is_completed' => in_array($l->id, $completedLessonIds, true),
                    'status' => $status,
                    'is_locked' => $status === 'locked',
                    'is_current' => $isCurrent,
                ];
            }
        }

        return Inertia::render('Lessons/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'description' => 'Part of the course: '.$course->title,
                'video_url' => $lesson->video_url,
                'video_provider' => $lesson->video_provider,
                'youtube_video_id' => $lesson->youtube_video_id,
                'duration' => $this->formatDuration($lesson->duration_seconds),
                'video_duration_seconds' => $lesson->video_duration_seconds,
                'requires_reflection' => (bool) $lesson->requires_reflection,
                'reflection_requires_approval' => (bool) $lesson->reflection_requires_approval,
                'transcript_text' => $lesson->transcript_text,
                'transcript_segments' => $lesson->transcriptSegments()
                    ->get(['id', 'start_seconds', 'end_seconds', 'text'])
                    ->toArray(),
                'next_lesson_id' => $nextLessonId,
                'prev_lesson_id' => $prevLessonId,
                'status' => $statusByLessonId[$lesson->id] ?? 'available',
                'is_locked' => ($statusByLessonId[$lesson->id] ?? 'available') === 'locked',
            ],
            'playlist' => $playlist,
            'completedLessonIds' => $completedLessonIds,
            'reflection' => $reflectionData,
        ]);
    }

    private function formatDuration(?int $seconds): string
    {
        if (!$seconds) return '0m';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }
}

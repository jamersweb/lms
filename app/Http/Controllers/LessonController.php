<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\EligibilityService;
use App\Services\JourneyService;
use App\Services\ProgressionService;
use App\Support\LockMessage;
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
        $course = Course::with(['modules.lessons', 'contentRule'])->findOrFail($courseId);
        $lesson = Lesson::with([
            'module.contentRule',
            'module.course.contentRule',
            'reflections',
            'transcriptSegments',
            'contentRule',
            'task'
        ])->findOrFail($lessonId);

        $user = Auth::user();

        // Check progression (combines eligibility + sequential unlocking)
        if ($user) {
            $progressionService = app(ProgressionService::class);
            $result = $progressionService->canAccessLesson($user, $lesson);

            if (!$result->allowed) {
                // Build lock message
                $lockMessage = LockMessage::fromEligibility($result);

                // Add sequential-specific messages
                if (in_array('previous_lesson_incomplete', $result->reasons)) {
                    $previousLesson = $progressionService->getPreviousLesson($lesson);
                    if ($previousLesson) {
                        $lockMessage = 'Complete previous lesson first: ' . $previousLesson->title;
                    } else {
                        $lockMessage = 'Complete previous lesson first';
                    }
                } elseif (in_array('not_next_lesson', $result->reasons)) {
                    $lockMessage = 'Please complete lessons in order';
                } elseif (in_array('reflection_required', $result->reasons)) {
                    $lockMessage = 'Submit your reflection for the previous lesson to continue';
                } elseif (in_array('task_incomplete', $result->reasons)) {
                    $previousLesson = $progressionService->getPreviousLesson($lesson);
                    if ($previousLesson && $previousLesson->task) {
                        $taskProgress = \App\Models\TaskProgress::where('task_id', $previousLesson->task->id)
                            ->where('user_id', $user->id)
                            ->first();
                        $daysDone = $taskProgress ? $taskProgress->days_done : 0;
                        $lockMessage = sprintf('Complete the practice task (Day %d of %d) to continue', $daysDone, $previousLesson->task->required_days);
                    } else {
                        $lockMessage = 'Complete the practice task to continue';
                    }
                } elseif (in_array('not_released_yet', $result->reasons)) {
                    $releaseScheduleService = app(\App\Services\ReleaseScheduleService::class);
                    $remaining = $releaseScheduleService->getRemaining($user, $lesson);
                    if ($remaining['release_at']) {
                        $releaseAt = \Carbon\Carbon::parse($remaining['release_at']);
                        $lockMessage = sprintf('This lesson will be available on %s', $releaseAt->format('M j, Y g:i A'));
                    } else {
                        $lockMessage = 'This lesson is not available yet';
                    }
                }

                return redirect()->route('courses.show', $course)
                    ->with('error', 'This lesson is locked: ' . $lockMessage);
            }
        }

        // Check enrollment (unless it's a free preview)
        if ($user && ! $lesson->is_free_preview && ! $user->isEnrolledIn($course->id)) {
            return redirect()->route('courses.show', $course)
                ->with('error', 'You must enroll in this course to access the lessons.');
        }

        $completedLessonIds = [];
        $statusByLessonId = [];

        $reflectionData = null;

        $lessonNotes = [];

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

                // Get current lesson progress for display
                $currentLessonProgress = $progress->where('lesson_id', $lesson->id)->first();

                $userReflection = $lesson->reflections
                    ->where('user_id', $user->id)
                    ->first();

                if ($userReflection) {
                    $reflectionData = [
                        'takeaway' => $userReflection->takeaway ?? $userReflection->content ?? '',
                        'content' => $userReflection->takeaway ?? $userReflection->content ?? '', // Keep for backward compatibility
                        'submitted_at' => $userReflection->submitted_at,
                        'review_status' => $userReflection->review_status,
                        'teacher_note' => $userReflection->teacher_note ?? $userReflection->mentor_note ?? null,
                        'mentor_note' => $userReflection->teacher_note ?? $userReflection->mentor_note ?? null, // Keep for backward compatibility
                    ];
                }

                // Load task data for current lesson
                $taskData = null;
                if ($lesson->task) {
                    $taskProgress = \App\Models\TaskProgress::where('task_id', $lesson->task->id)
                        ->where('user_id', $user->id)
                        ->first();

                    $taskData = [
                        'id' => $lesson->task->id,
                        'title' => $lesson->task->title,
                        'instructions' => $lesson->task->instructions,
                        'required_days' => $lesson->task->required_days,
                        'unlock_next_lesson' => $lesson->task->unlock_next_lesson,
                        'progress' => $taskProgress ? [
                            'status' => $taskProgress->status,
                            'days_done' => $taskProgress->days_done,
                            'last_checkin_on' => $taskProgress->last_checkin_on?->toDateString(),
                            'has_checked_in_today' => $taskProgress->hasCheckedInToday(),
                            'completed_at' => $taskProgress->completed_at?->toIso8601String(),
                        ] : null,
                    ];
                }
            }

            // Load notes for this lesson
            $lessonNotes = \App\Models\Note::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->latest()
                ->get()
                ->map(function($note) {
                    return [
                        'id' => $note->id,
                        'title' => $note->title,
                        'content' => $note->content,
                        'pinned' => $note->pinned,
                        'updated_at' => $note->updated_at->diffForHumans(),
                        'updated_at_raw' => $note->updated_at->toISOString(),
                    ];
                })
                ->toArray();
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
                'duration_seconds' => $lesson->duration_seconds,
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
                'is_completed' => in_array($lesson->id, $completedLessonIds, true),
                'progress' => $currentLessonProgress ? [
                    'watched_seconds' => $currentLessonProgress->watched_seconds ?? 0,
                    'max_playback_rate' => $currentLessonProgress->max_playback_rate ?? 1.0,
                    'seek_attempts' => $currentLessonProgress->seek_attempts ?? 0,
                ] : null,
            ],
            'playlist' => $playlist,
            'completedLessonIds' => $completedLessonIds,
            'reflection' => $reflectionData,
            'task' => $taskData ?? null,
            'notes' => $lessonNotes,
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

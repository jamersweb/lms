<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\CourseProgressSnapshot;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\Task;
use App\Models\TaskProgress;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\ReleaseScheduleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCourseProgressSnapshotsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $progressionService = app(ProgressionService::class);
        $releaseScheduleService = app(ReleaseScheduleService::class);

        // Process enrollments in chunks to avoid memory issues
        Enrollment::with(['user', 'course'])
            ->chunk(100, function ($enrollments) use ($progressionService, $releaseScheduleService) {
                foreach ($enrollments as $enrollment) {
            $user = $enrollment->user;
            $course = $enrollment->course;

            if (!$user || !$course) {
                continue;
            }

            // Load course structure
            $course->load(['modules.lessons']);

            // Count totals
            $lessonsTotal = 0;
            $lessonsCompleted = 0;
            $reflectionsRequired = 0;
            $reflectionsDone = 0;
            $tasksRequired = 0;
            $tasksDone = 0;

            // Get all lessons in course
            $allLessons = collect();
            foreach ($course->modules as $module) {
                $allLessons = $allLessons->merge($module->lessons);
            }

            $lessonsTotal = $allLessons->count();

            // Get user's progress for all lessons
            $progressByLesson = LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $allLessons->pluck('id'))
                ->get()
                ->keyBy('lesson_id');

            // Batch load all reflections for this user in this course
            $reflectionsByLesson = LessonReflection::where('user_id', $user->id)
                ->whereIn('lesson_id', $allLessons->pluck('id'))
                ->get()
                ->keyBy('lesson_id');

            // Count completed lessons and reflections
            // Note: Each completed lesson requires a reflection (for progression to next lesson)
            foreach ($allLessons as $lesson) {
                $progress = $progressByLesson->get($lesson->id);
                if ($progress && $progress->completed_at) {
                    $lessonsCompleted++;

                    // Reflection is required for each completed lesson
                    $reflectionsRequired++;
                    if ($reflectionsByLesson->has($lesson->id)) {
                        $reflectionsDone++;
                    }
                }
            }

            // Count tasks
            foreach ($allLessons as $lesson) {
                if ($lesson->task && $lesson->task->unlock_next_lesson) {
                    $tasksRequired++;
                    $taskProgress = TaskProgress::where('task_id', $lesson->task->id)
                        ->where('user_id', $user->id)
                        ->first();
                    if ($taskProgress && $taskProgress->status === TaskProgress::STATUS_COMPLETED) {
                        $tasksDone++;
                    }
                }
            }

            // Determine next lesson and blocked_by
            $nextLesson = null;
            $nextLessonReleaseAt = null;
            $blockedBy = null;

            // Find first incomplete lesson
            foreach ($course->modules as $module) {
                $moduleLessons = $module->lessons->sortBy('sort_order');
                foreach ($moduleLessons as $lesson) {
                    $progress = $progressByLesson->get($lesson->id);
                    if (!$progress || !$progress->completed_at) {
                        $nextLesson = $lesson;
                        break 2; // Break both loops
                    }
                }
            }

            if ($nextLesson) {
                // Check why it's blocked
                $progressionResult = $progressionService->canAccessLesson($user, $nextLesson);

                if (!$progressionResult->allowed) {
                    $reasons = $progressionResult->reasons ?? [];
                    if (in_array('reflection_required', $reasons)) {
                        $blockedBy = CourseProgressSnapshot::BLOCKED_REFLECTION_REQUIRED;
                    } elseif (in_array('task_incomplete', $reasons)) {
                        $blockedBy = CourseProgressSnapshot::BLOCKED_TASK_INCOMPLETE;
                    } elseif (in_array('not_released_yet', $reasons)) {
                        $blockedBy = CourseProgressSnapshot::BLOCKED_NOT_RELEASED_YET;
                        $nextLessonReleaseAt = $releaseScheduleService->getLessonReleaseAt($user, $nextLesson);
                    } elseif (in_array('previous_lesson_incomplete', $reasons)) {
                        $blockedBy = CourseProgressSnapshot::BLOCKED_PREVIOUS_LESSON_INCOMPLETE;
                    } elseif (in_array('not_next_lesson', $reasons)) {
                        $blockedBy = CourseProgressSnapshot::BLOCKED_NOT_NEXT_LESSON;
                    }
                } else {
                    // Lesson is accessible
                    $nextLessonReleaseAt = $releaseScheduleService->getLessonReleaseAt($user, $nextLesson);
                }
            }

            // Upsert snapshot
            CourseProgressSnapshot::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ],
                [
                    'lessons_total' => $lessonsTotal,
                    'lessons_completed' => $lessonsCompleted,
                    'reflections_required' => $reflectionsRequired,
                    'reflections_done' => $reflectionsDone,
                    'tasks_required' => $tasksRequired,
                    'tasks_done' => $tasksDone,
                    'next_lesson_id' => $nextLesson?->id,
                    'next_lesson_release_at' => $nextLessonReleaseAt,
                    'blocked_by' => $blockedBy,
                    'updated_at' => now(),
                ]
            );
                }
            });
    }
}

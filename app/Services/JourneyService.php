<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;

class JourneyService
{
    /**
     * Ensure lesson_progress rows exist for all lessons in the course
     * and initialize their status (first available, rest locked).
     */
    public static function ensureProgressRecords(User $user, Course $course): void
    {
        $course->loadMissing('modules.lessons');

        $lessons = $course->modules
            ->flatMap->lessons
            ->sortBy(['sort_order', 'id']);

        $lessonIds = $lessons->pluck('id')->unique()->values();

        if ($lessonIds->isEmpty()) {
            return;
        }

        $existing = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        foreach ($lessonIds as $lessonId) {
            if (! isset($existing[$lessonId])) {
                LessonProgress::create([
                    'user_id' => $user->id,
                    'lesson_id' => $lessonId,
                    'status' => 'locked',
                    'is_completed' => false,
                    'last_position_seconds' => 0,
                ]);
            }
        }

        // Drip schedule: 1 lesson per day after enrollment
        $now = now();
        $offset = 0;

        foreach ($lessons as $lesson) {
            /** @var LessonProgress $progress */
            $progress = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            if (! $progress) {
                continue;
            }

            if (! $progress->available_at) {
                $progress->available_at = $now->copy()->addDays($offset);
                $progress->save();
            }

            $offset++;
        }

        self::computeStatusesForCourse($user, $course);
    }

    /**
     * Recompute statuses across all modules in a course.
     */
    public static function computeStatusesForCourse(User $user, Course $course): void
    {
        $course->loadMissing('modules.lessons');

        foreach ($course->modules as $module) {
            self::computeStatuses($user, $module);
        }
    }

    /**
     * Compute sequential statuses for all lessons in a module:
     * - completed lessons => status 'completed'
     * - first incomplete lesson => status 'available'
     * - all subsequent incomplete lessons => status 'locked'
     */
    public static function computeStatuses(User $user, Module $module): void
    {
        $lessons = $module->lessons()->orderBy('sort_order')->orderBy('id')->get();

        if ($lessons->isEmpty()) {
            return;
        }

        $lessonIds = $lessons->pluck('id');

        $progressByLesson = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');

        $now = now();
        $previousSatisfied = true; // used to unlock only the first incomplete lesson
        $previousLesson = null;

        foreach ($lessons as $lesson) {
            /** @var LessonProgress $progress */
            $progress = $progressByLesson->get($lesson->id);

            if (! $progress) {
                $progress = new LessonProgress([
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                    'last_position_seconds' => 0,
                ]);
            }

            $isCompleted = (bool) $progress->completed_at;
            $currentStatus = $progress->status ?? 'locked';

            if ($isCompleted) {
                $currentStatus = 'completed';
                $previousSatisfied = true;
            } else {
                if ($previousSatisfied) {
                    // Check additional gates based on previous lesson (tasks, reflections etc.)
                    if ($previousLesson && ! self::reflectionSatisfied($user, $previousLesson)) {
                        $currentStatus = 'locked';
                        $previousSatisfied = false;
                    } else {
                        // First incomplete lesson becomes available
                        if ($currentStatus !== 'available' && $currentStatus !== 'in_progress') {
                            $progress->available_at = $progress->available_at ?? $now;
                            $progress->unlocked_at = $progress->unlocked_at ?? $now;
                        }

                        // Preserve in_progress if already started
                        $currentStatus = $currentStatus === 'in_progress' ? 'in_progress' : 'available';
                        $previousSatisfied = false;
                    }
                } else {
                    $currentStatus = 'locked';
                }
            }

            $progress->status = $currentStatus;
            $progress->save();

            $previousLesson = $lesson;
        }
    }

    protected static function reflectionSatisfied(User $user, Lesson $lesson): bool
    {
        if (! $lesson->requires_reflection) {
            return true;
        }

        $query = $lesson->reflections()
            ->where('user_id', $user->id);

        if ($lesson->reflection_requires_approval) {
            $query->where('review_status', 'approved');
        }

        return $query->exists();
    }
}


<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\Task;
use App\Models\TaskProgress;
use App\Models\User;
use App\Services\ReleaseScheduleService;
use App\Support\EligibilityResult;

/**
 * Service for determining lesson progression and sequential unlocking.
 *
 * Combines:
 * - EligibilityService (Phase 1: gender/bayah/level gating)
 * - Sequential unlocking rules (this task)
 */
class ProgressionService
{
    public function __construct(
        private EligibilityService $eligibilityService,
        private ReleaseScheduleService $releaseScheduleService
    ) {}

    /**
     * Check if user can access a lesson, considering both eligibility and sequential progression.
     *
     * Returns EligibilityResult with reasons including:
     * - Phase 1 reasons: gender_mismatch, requires_bayah, level_too_low, conflicting_gender_rules
     * - Sequential reasons: previous_lesson_incomplete, not_next_lesson
     */
    public function canAccessLesson(User $user, Lesson $lesson): EligibilityResult
    {
        // First check Phase 1 eligibility (gender/bayah/level)
        $eligibilityResult = $this->eligibilityService->canAccessLesson($user, $lesson);

        if (!$eligibilityResult->allowed) {
            return $eligibilityResult; // Return early if basic eligibility fails
        }

        // Check sequential unlocking if enabled
        if (!config('progression.sequential_lessons', true)) {
            return EligibilityResult::allow(); // Sequential disabled, allow if eligible
        }

        // Get previous lesson in same module
        $previousLesson = $this->getPreviousLesson($lesson);

        if ($previousLesson === null) {
            // First lesson in module - always accessible if eligible
            return EligibilityResult::allow();
        }

        // Check if previous lesson is completed
        $previousProgress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $previousLesson->id)
            ->first();

        $previousCompleted = $previousProgress && $previousProgress->completed_at !== null;

        if (!$previousCompleted) {
            // Previous lesson not completed - deny access
            return EligibilityResult::deny(
                reasons: ['previous_lesson_incomplete'],
                requiredLevel: null,
                requiredGender: null,
                requiresBayah: false,
            );
        }

        // Phase 3: Check if reflection is required and submitted
        // If previous lesson is completed, reflection must be submitted to unlock next lesson
        $previousReflection = LessonReflection::where('user_id', $user->id)
            ->where('lesson_id', $previousLesson->id)
            ->exists();

        if (!$previousReflection) {
            // Previous lesson completed but reflection not submitted - deny access
            return EligibilityResult::deny(
                reasons: ['reflection_required'],
                requiredLevel: null,
                requiredGender: null,
                requiresBayah: false,
            );
        }

        // Phase 3 Task 3: Check if task is required and completed
        // If previous lesson has a task with unlock_next_lesson=true, it must be completed
        $previousTask = $previousLesson->task;
        if ($previousTask && $previousTask->unlock_next_lesson) {
            $taskProgress = TaskProgress::where('task_id', $previousTask->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$taskProgress || $taskProgress->status !== TaskProgress::STATUS_COMPLETED) {
                // Previous lesson has task that is not completed - deny access
                return EligibilityResult::deny(
                    reasons: ['task_incomplete'],
                    requiredLevel: null,
                    requiredGender: null,
                    requiresBayah: false,
                );
            }
        }

        // If one-at-a-time mode, check if this is the next lesson
        if (config('progression.one_at_a_time', true)) {
            // Ensure module is loaded
            if (!$lesson->relationLoaded('module')) {
                $lesson->load('module');
            }

            $firstIncomplete = $this->getFirstIncompleteLessonInModule($user, $lesson->module);

            if ($firstIncomplete && $firstIncomplete->id !== $lesson->id) {
                // Not the next lesson - deny access
                return EligibilityResult::deny(
                    reasons: ['not_next_lesson'],
                    requiredLevel: null,
                    requiredGender: null,
                    requiresBayah: false,
                );
            }
        }

        // Phase 3 Task 4: Check release schedule (drip release)
        // This applies to the lesson being accessed, not just the next one
        if (!$this->releaseScheduleService->isReleased($user, $lesson)) {
            $releaseAt = $this->releaseScheduleService->getLessonReleaseAt($user, $lesson);
            return EligibilityResult::deny(
                reasons: ['not_released_yet'],
                requiredLevel: null,
                requiredGender: null,
                requiresBayah: false,
            );
        }

        // All checks passed
        return EligibilityResult::allow();
    }

    /**
     * Get the previous lesson in the same module (by sort_order).
     *
     * Returns the lesson with the highest sort_order that is less than the current lesson's sort_order.
     */
    public function getPreviousLesson(Lesson $lesson): ?Lesson
    {
        // Ensure module_id is available
        $moduleId = $lesson->module_id ?? $lesson->module->id ?? null;
        if (!$moduleId) {
            return null;
        }

        return Lesson::where('module_id', $moduleId)
            ->where('sort_order', '<', $lesson->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    /**
     * Get the first incomplete lesson in a module.
     *
     * Returns the lesson with the lowest sort_order that is not completed.
     */
    public function getFirstIncompleteLessonInModule(User $user, Module $module): ?Lesson
    {
        $lessons = $module->lessons()->orderBy('sort_order')->orderBy('id')->get();

        if ($lessons->isEmpty()) {
            return null;
        }

        $progressByLesson = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->get()
            ->keyBy('lesson_id');

        foreach ($lessons as $lesson) {
            $progress = $progressByLesson->get($lesson->id);

            if (!$progress || !$progress->completed_at) {
                return $lesson;
            }
        }

        // All lessons completed
        return null;
    }

    /**
     * Get the next lesson that should be accessible for a user in a module.
     *
     * Returns the first incomplete lesson, or null if all are completed.
     */
    public function getNextLessonInModule(User $user, Module $module): ?Lesson
    {
        return $this->getFirstIncompleteLessonInModule($user, $module);
    }
}

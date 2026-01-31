<?php

namespace App\Services;

use App\Models\ContentRule;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Support\EligibilityResult;

/**
 * Service for evaluating user eligibility to access content.
 *
 * Example usage:
 * $result = app(EligibilityService::class)->canAccessLesson(auth()->user(), $lesson);
 * if (!$result->allowed) {
 *     // Show lock message with $result->reasons
 * }
 */
class EligibilityService
{
    /**
     * Level ranking for comparison.
     */
    private const LEVEL_RANK = [
        'beginner' => 1,
        'intermediate' => 2,
        'expert' => 3,
    ];

    /**
     * Check if user can access a course.
     */
    public function canAccessCourse(User $user, Course $course): EligibilityResult
    {
        $rules = $this->collectCourseRules($course);
        return $this->evaluate($user, $rules);
    }

    /**
     * Check if user can access a module.
     *
     * Evaluates rules in order: Course → Module
     */
    public function canAccessModule(User $user, Module $module): EligibilityResult
    {
        $rules = $this->collectModuleRules($module);
        return $this->evaluate($user, $rules);
    }

    /**
     * Check if user can access a lesson.
     *
     * Evaluates rules in order: Course → Module → Lesson
     */
    public function canAccessLesson(User $user, Lesson $lesson): EligibilityResult
    {
        $rules = $this->collectLessonRules($lesson);
        return $this->evaluate($user, $rules);
    }

    /**
     * Collect rules for a course (only course rule).
     */
    private function collectCourseRules(Course $course): array
    {
        $rules = [];

        if (!$course->relationLoaded('contentRule')) {
            $course->load('contentRule');
        }

        if ($course->contentRule) {
            $rules[] = $course->contentRule;
        }

        return $rules;
    }

    /**
     * Collect rules for a module (course + module).
     */
    private function collectModuleRules(Module $module): array
    {
        $rules = [];

        // Load course with its rule if not already loaded
        if (!$module->relationLoaded('course')) {
            $module->load('course.contentRule');
        } elseif ($module->course && !$module->course->relationLoaded('contentRule')) {
            $module->course->load('contentRule');
        }

        // Course rule
        if ($module->course && $module->course->contentRule) {
            $rules[] = $module->course->contentRule;
        }

        // Module rule
        if (!$module->relationLoaded('contentRule')) {
            $module->load('contentRule');
        }

        if ($module->contentRule) {
            $rules[] = $module->contentRule;
        }

        return $rules;
    }

    /**
     * Collect rules for a lesson (course + module + lesson).
     */
    private function collectLessonRules(Lesson $lesson): array
    {
        $rules = [];

        // Load module and course with their rules if not already loaded
        if (!$lesson->relationLoaded('module')) {
            $lesson->load('module.course.contentRule', 'module.contentRule', 'contentRule');
        } else {
            if (!$lesson->module->relationLoaded('course')) {
                $lesson->module->load('course.contentRule');
            } elseif ($lesson->module->course && !$lesson->module->course->relationLoaded('contentRule')) {
                $lesson->module->course->load('contentRule');
            }

            if (!$lesson->module->relationLoaded('contentRule')) {
                $lesson->module->load('contentRule');
            }
        }

        // Load lesson rule if not loaded
        if (!$lesson->relationLoaded('contentRule')) {
            $lesson->load('contentRule');
        }

        // Course rule
        if ($lesson->module && $lesson->module->course && $lesson->module->course->contentRule) {
            $rules[] = $lesson->module->course->contentRule;
        }

        // Module rule
        if ($lesson->module && $lesson->module->contentRule) {
            $rules[] = $lesson->module->contentRule;
        }

        // Lesson rule
        if ($lesson->contentRule) {
            $rules[] = $lesson->contentRule;
        }

        return $rules;
    }

    /**
     * Evaluate user eligibility against a collection of rules.
     *
     * Rules are additive (AND behavior):
     * - If any rule requires bay'ah → bay'ah required
     * - If any rule specifies gender → must match (if conflict, deny)
     * - For level: enforce the highest min_level among all rules
     */
    private function evaluate(User $user, array $rules): EligibilityResult
    {
        // No rules = allowed
        if (empty($rules)) {
            return EligibilityResult::allow();
        }

        // Compute effective requirements from all rules
        $effectiveRequiresBayah = false;
        $genders = [];
        $minLevels = [];

        foreach ($rules as $rule) {
            if ($rule->requires_bayah) {
                $effectiveRequiresBayah = true;
            }

            if ($rule->gender !== null) {
                $genders[] = $rule->gender;
            }

            if ($rule->min_level !== null) {
                $minLevels[] = $rule->min_level;
            }
        }

        // Check for conflicting gender rules
        $uniqueGenders = array_unique($genders);
        if (count($uniqueGenders) > 1) {
            return EligibilityResult::deny(
                reasons: ['conflicting_gender_rules'],
                requiresBayah: $effectiveRequiresBayah,
            );
        }

        $effectiveGender = !empty($uniqueGenders) ? reset($uniqueGenders) : null;

        // Determine effective min level (highest rank)
        $effectiveMinLevel = null;
        if (!empty($minLevels)) {
            $maxRank = 0;
            foreach ($minLevels as $level) {
                $rank = self::LEVEL_RANK[$level] ?? 1;
                if ($rank > $maxRank) {
                    $maxRank = $rank;
                    $effectiveMinLevel = $level;
                }
            }
        }

        // Check user against effective requirements
        $reasons = [];

        // Check bay'ah requirement
        if ($effectiveRequiresBayah && !$user->has_bayah) {
            $reasons[] = 'requires_bayah';
        }

        // Check gender requirement
        if ($effectiveGender !== null) {
            $userGender = $user->gender;
            if ($userGender !== $effectiveGender) {
                $reasons[] = 'gender_mismatch';
            }
        }

        // Check level requirement
        if ($effectiveMinLevel !== null) {
            $userLevel = $user->level ?: 'beginner';
            $userRank = self::LEVEL_RANK[$userLevel] ?? 1;
            $requiredRank = self::LEVEL_RANK[$effectiveMinLevel] ?? 1;

            if ($userRank < $requiredRank) {
                $reasons[] = 'level_too_low';
            }
        }

        // If any reason found, deny
        if (!empty($reasons)) {
            return EligibilityResult::deny(
                reasons: $reasons,
                requiredLevel: $effectiveMinLevel,
                requiredGender: $effectiveGender,
                requiresBayah: $effectiveRequiresBayah,
            );
        }

        // All checks passed
        return EligibilityResult::allow();
    }
}

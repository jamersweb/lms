<?php

namespace App\Support;

use App\Support\EligibilityResult;

/**
 * Helper class to format eligibility results into user-friendly lock messages.
 */
class LockMessage
{
    /**
     * Generate a human-readable lock message from an EligibilityResult.
     *
     * Messages are ordered: requires_bayah → required_level → required_gender
     * Always returns a string (never empty for denied results).
     */
    public static function fromEligibility(EligibilityResult $result): string
    {
        if ($result->allowed) {
            return '';
        }

        // Handle conflicting gender rules first (highest priority)
        if (in_array('conflicting_gender_rules', $result->reasons)) {
            return 'Misconfigured access rule. Please contact support.';
        }

        $messages = [];

        // Order: 1. requires_bayah, 2. required_level, 3. required_gender
        if (in_array('requires_bayah', $result->reasons)) {
            $messages[] = 'Bay\'ah required';
        }

        if (in_array('level_too_low', $result->reasons)) {
            if ($result->requiredLevel) {
                $levelLabel = ucfirst($result->requiredLevel);
                $messages[] = "Requires {$levelLabel} level";
            } else {
                $messages[] = 'Level requirement not met';
            }
        }

        if (in_array('gender_mismatch', $result->reasons)) {
            if ($result->requiredGender === 'male') {
                $messages[] = 'Available for brothers only';
            } elseif ($result->requiredGender === 'female') {
                $messages[] = 'Available for sisters only';
            } else {
                $messages[] = 'Gender restriction';
            }
        }

        // Sequential progression reasons
        if (in_array('previous_lesson_incomplete', $result->reasons)) {
            $messages[] = 'Complete previous lesson first';
        }

        if (in_array('not_next_lesson', $result->reasons)) {
            $messages[] = 'Please complete lessons in order';
        }

        // Phase 3: Reflection requirement
        if (in_array('reflection_required', $result->reasons)) {
            $messages[] = 'Submit your reflection for the previous lesson to continue';
        }

        // Phase 3 Task 3: Task requirement
        if (in_array('task_incomplete', $result->reasons)) {
            $messages[] = 'Complete the practice task to continue';
        }

        // Phase 3 Task 4: Release schedule (drip)
        if (in_array('not_released_yet', $result->reasons)) {
            // This will be handled separately with release_at info
            $messages[] = 'This lesson is not available yet';
        }

        // Fallback: if no messages were added but result is denied, return generic message
        if (empty($messages)) {
            return 'Access restricted';
        }

        return implode(' • ', $messages);
    }
}

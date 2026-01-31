<?php

namespace App\Support;

/**
 * DTO representing the result of an eligibility check.
 *
 * Example usage:
 * $result = app(EligibilityService::class)->canAccessLesson(auth()->user(), $lesson);
 * if (!$result->allowed) {
 *     // Show lock message with $result->reasons
 * }
 */
class EligibilityResult
{
    public function __construct(
        public bool $allowed,
        public array $reasons = [],
        public ?string $requiredLevel = null,
        public ?string $requiredGender = null,
        public bool $requiresBayah = false,
    ) {}

    /**
     * Create an allowed result.
     */
    public static function allow(): self
    {
        return new self(allowed: true);
    }

    /**
     * Create a denied result.
     *
     * @param array $reasons Array of reason codes: 'gender_mismatch', 'requires_bayah', 'level_too_low', 'conflicting_gender_rules'
     * @param string|null $requiredLevel The minimum level required (beginner, intermediate, expert)
     * @param string|null $requiredGender The required gender (male, female)
     * @param bool $requiresBayah Whether bay'ah is required
     */
    public static function deny(
        array $reasons,
        ?string $requiredLevel = null,
        ?string $requiredGender = null,
        bool $requiresBayah = false
    ): self {
        return new self(
            allowed: false,
            reasons: $reasons,
            requiredLevel: $requiredLevel,
            requiredGender: $requiredGender,
            requiresBayah: $requiresBayah,
        );
    }
}

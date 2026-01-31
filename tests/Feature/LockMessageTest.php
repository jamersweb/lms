<?php

namespace Tests\Feature;

use App\Support\EligibilityResult;
use App\Support\LockMessage;
use Tests\TestCase;

class LockMessageTest extends TestCase
{
    /** @test */
    public function conflicting_gender_rules_returns_misconfigured_message(): void
    {
        $result = EligibilityResult::deny(
            reasons: ['conflicting_gender_rules'],
        );

        $message = LockMessage::fromEligibility($result);

        $this->assertSame('Misconfigured access rule. Please contact support.', $message);
    }

    /** @test */
    public function multiple_reasons_ordered_correctly(): void
    {
        $result = EligibilityResult::deny(
            reasons: ['requires_bayah', 'level_too_low', 'gender_mismatch'],
            requiredLevel: 'expert',
            requiredGender: 'male',
            requiresBayah: true,
        );

        $message = LockMessage::fromEligibility($result);

        // Should be ordered: bay'ah → level → gender
        $this->assertStringStartsWith("Bay'ah required", $message);
        $this->assertStringContainsString('Requires Expert level', $message);
        $this->assertStringContainsString('Available for brothers only', $message);

        // Verify order by checking positions
        $bayahPos = strpos($message, "Bay'ah required");
        $levelPos = strpos($message, 'Requires Expert level');
        $genderPos = strpos($message, 'Available for brothers only');

        $this->assertLessThan($levelPos, $bayahPos);
        $this->assertLessThan($genderPos, $levelPos);
    }

    /** @test */
    public function requires_bayah_only_returns_bayah_message(): void
    {
        $result = EligibilityResult::deny(
            reasons: ['requires_bayah'],
            requiresBayah: true,
        );

        $message = LockMessage::fromEligibility($result);

        $this->assertSame("Bay'ah required", $message);
    }

    /** @test */
    public function level_too_low_returns_level_message(): void
    {
        $result = EligibilityResult::deny(
            reasons: ['level_too_low'],
            requiredLevel: 'expert',
        );

        $message = LockMessage::fromEligibility($result);

        $this->assertSame('Requires Expert level', $message);
    }

    /** @test */
    public function gender_mismatch_male_returns_brothers_only(): void
    {
        $result = EligibilityResult::deny(
            reasons: ['gender_mismatch'],
            requiredGender: 'male',
        );

        $message = LockMessage::fromEligibility($result);

        $this->assertSame('Available for brothers only', $message);
    }

    /** @test */
    public function gender_mismatch_female_returns_sisters_only(): void
    {
        $result = EligibilityResult::deny(
            reasons: ['gender_mismatch'],
            requiredGender: 'female',
        );

        $message = LockMessage::fromEligibility($result);

        $this->assertSame('Available for sisters only', $message);
    }

    /** @test */
    public function allowed_result_returns_empty_string(): void
    {
        $result = EligibilityResult::allow();

        $message = LockMessage::fromEligibility($result);

        $this->assertSame('', $message);
    }

    /** @test */
    public function empty_reasons_returns_fallback_message(): void
    {
        $result = new EligibilityResult(
            allowed: false,
            reasons: [],
        );

        $message = LockMessage::fromEligibility($result);

        $this->assertSame('Access restricted', $message);
    }

    /** @test */
    public function bayah_and_level_combined_message(): void
    {
        $result = EligibilityResult::deny(
            reasons: ['requires_bayah', 'level_too_low'],
            requiredLevel: 'intermediate',
            requiresBayah: true,
        );

        $message = LockMessage::fromEligibility($result);

        $this->assertStringContainsString("Bay'ah required", $message);
        $this->assertStringContainsString('Requires Intermediate level', $message);
        $this->assertStringContainsString(' • ', $message);
    }
}

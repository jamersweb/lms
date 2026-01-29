<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\User;

class ContentGatingService
{
    public static function userCanAccessLesson(User $user, Lesson $lesson): bool
    {
        $gender = $user->gender;
        $hasBayah = (bool) $user->has_bayah;
        $level = $user->level ?: 'beginner';

        $allowedGender = $lesson->allowed_gender ?: 'all';
        $requiresBayah = (bool) $lesson->requires_bayah;
        $minLevel = $lesson->min_level ?: 'beginner';

        // Gender gating
        if ($allowedGender === 'male' && $gender !== 'male') {
            return false;
        }

        if ($allowedGender === 'female' && $gender !== 'female') {
            return false;
        }

        // Bay'ah gating
        if ($requiresBayah && !$hasBayah) {
            return false;
        }

        // Level gating
        $rank = [
            'beginner' => 1,
            'intermediate' => 2,
            'expert' => 3,
        ];

        $userRank = $rank[$level] ?? 1;
        $minRank = $rank[$minLevel] ?? 1;

        if ($userRank < $minRank) {
            return false;
        }

        return true;
    }
}


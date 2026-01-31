<?php

namespace App\Services;

use App\Models\User;

/**
 * Service for filtering users based on audience criteria.
 * Used for micro-nudge campaigns and other segmentation needs.
 */
class AudienceFilterService
{
    /**
     * Check if a user matches the given audience filters.
     *
     * @param User $user
     * @param array|null $filters Array with keys: min_level, requires_bayah, gender
     * @return bool
     */
    public function matches(User $user, ?array $filters): bool
    {
        if (!$filters || empty($filters)) {
            return true; // No filters = all users match
        }

        // Check min_level
        if (isset($filters['min_level']) && $filters['min_level']) {
            $userLevelRank = $this->getLevelRank($user->level ?? 'beginner');
            $requiredLevelRank = $this->getLevelRank($filters['min_level']);

            if ($userLevelRank < $requiredLevelRank) {
                return false; // User level too low
            }
        }

        // Check requires_bayah
        if (isset($filters['requires_bayah']) && $filters['requires_bayah'] === true) {
            if (!$user->has_bayah) {
                return false; // Bay'ah required but user doesn't have it
            }
        }

        // Check gender
        if (isset($filters['gender']) && $filters['gender']) {
            if (!$user->gender) {
                return false; // Gender filter set but user has no gender
            }

            if ($user->gender !== $filters['gender']) {
                return false; // Gender mismatch
            }
        }

        return true; // All filters passed
    }

    /**
     * Get numeric rank for level (for comparison).
     *
     * @param string $level
     * @return int
     */
    private function getLevelRank(string $level): int
    {
        return match ($level) {
            'beginner' => 1,
            'intermediate' => 2,
            'expert' => 3,
            default => 1, // Default to beginner
        };
    }

    /**
     * Filter a collection of users based on audience filters.
     *
     * @param \Illuminate\Database\Eloquent\Collection|array $users
     * @param array|null $filters
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    public function filter($users, ?array $filters)
    {
        if (!$filters || empty($filters)) {
            return $users;
        }

        return collect($users)->filter(function ($user) use ($filters) {
            return $this->matches($user, $filters);
        })->values();
    }
}

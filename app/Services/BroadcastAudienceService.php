<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service for building audience queries for broadcasts.
 */
class BroadcastAudienceService
{
    /**
     * Build a query for users matching the given filters.
     *
     * @param array $filters Array with keys: min_level, requires_bayah, gender, course_id
     * @return Builder
     */
    public function query(array $filters): Builder
    {
        $query = User::query();

        // Filter by gender
        if (isset($filters['gender']) && $filters['gender']) {
            $query->where('gender', $filters['gender']);
        }

        // Filter by bay'ah requirement
        if (isset($filters['requires_bayah']) && $filters['requires_bayah'] === true) {
            $query->where('has_bayah', true);
        }

        // Filter by minimum level
        if (isset($filters['min_level']) && $filters['min_level']) {
            $levelRank = $this->getLevelRank($filters['min_level']);
            $query->whereRaw("CASE level
                WHEN 'beginner' THEN 1
                WHEN 'intermediate' THEN 2
                WHEN 'expert' THEN 3
                ELSE 1
            END >= ?", [$levelRank]);
        }

        // Filter by course enrollment
        if (isset($filters['course_id']) && $filters['course_id']) {
            $query->whereHas('enrollments', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        return $query;
    }

    /**
     * Count users matching the filters.
     *
     * @param array $filters
     * @return int
     */
    public function count(array $filters): int
    {
        return $this->query($filters)->count();
    }

    /**
     * Get a sample of users matching the filters (max 20).
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sample(array $filters, int $limit = 20)
    {
        return $this->query($filters)
            ->select('id', 'name', 'email', 'level', 'gender', 'has_bayah')
            ->limit($limit)
            ->get();
    }

    /**
     * Process users in chunks.
     *
     * @param array $filters
     * @param int $chunkSize
     * @param callable $callback
     * @return void
     */
    public function chunkedUsers(array $filters, int $chunkSize = 500, callable $callback): void
    {
        $this->query($filters)->chunk($chunkSize, $callback);
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
            default => 1,
        };
    }

    /**
     * Apply channel-specific opt-in filters to a query.
     *
     * @param Builder $query
     * @param string $channel
     * @return Builder
     */
    public function applyChannelOptIn(Builder $query, string $channel): Builder
    {
        if ($channel === Broadcast::CHANNEL_EMAIL) {
            $query->where('email_reminders_opt_in', true);
        } elseif ($channel === Broadcast::CHANNEL_WHATSAPP) {
            $query->where('whatsapp_opt_in', true)
                ->whereNotNull('whatsapp_number');
        }
        // in_app: no opt-in filter (always allowed)

        return $query;
    }
}

<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use App\Models\PointsEvent;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    /**
     * Evaluate all badges for a user and award if criteria met.
     */
    public function evaluateAndAward(User $user): array
    {
        $awarded = [];
        $badges = Badge::where('is_active', true)->get();

        // Pre-fetch some data to avoid N+1 queries if we had complex evaluators
        // For simple Phase 3, we might just query as needed or optimize later.
        
        foreach ($badges as $badge) {
            if ($user->badges()->where('badge_id', $badge->id)->exists()) {
                continue;
            }

            if ($this->checkCriteria($user, $badge->criteria)) {
                $user->badges()->attach($badge->id, ['earned_at' => now()]);
                $awarded[] = $badge;
            }
        }

        return $awarded;
    }

    protected function checkCriteria(User $user, array $criteria): bool
    {
        $type = $criteria['type'] ?? null;
        $count = $criteria['count'] ?? 1;

        if (!$type) return false;

        switch ($type) {
            case 'points':
                $totalPoints = PointsEvent::where('user_id', $user->id)->sum('points');
                return $totalPoints >= $count;

            case 'event_count':
                $eventType = $criteria['event_type'] ?? null;
                if (!$eventType) return false;
                
                $eventCount = PointsEvent::where('user_id', $user->id)
                    ->where('event_type', $eventType)
                    ->count();
                return $eventCount >= $count;

            default:
                return false;
        }
    }
}

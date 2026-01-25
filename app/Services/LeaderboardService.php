<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointsEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public function getTopUsers(int $limit = 10)
    {
        // Cache for 5 minutes
        return Cache::remember('leaderboard_top_' . $limit, 300, function () use ($limit) {
            $topUsers = PointsEvent::select('user_id', DB::raw('SUM(points) as total_points'))
                ->groupBy('user_id')
                ->orderByDesc('total_points')
                ->limit($limit)
                ->with(['user.settings'])
                ->get();

            return $topUsers->map(function ($entry) {
                $user = $entry->user;
                $settings = $user->settings;
                
                // If opt-out, return anonymous or skip (requirement says "exclude or show as Anonymous")
                // Let's show as Anonymous for now to keep the rank correct
                $isOptOut = $settings && $settings->leaderboard_opt_out;
                
                return [
                    'rank' => 0, // Assigned later
                    'name' => $isOptOut ? 'Anonymous User' : ($settings->display_name ?? $user->name),
                    'points' => (int) $entry->total_points,
                    'is_me' => false, // Can be set by controller
                    'user_id' => $isOptOut ? null : $user->id, // Conceal ID if opt-out
                ];
            });
        });
    }
}

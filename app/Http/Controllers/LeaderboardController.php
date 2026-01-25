<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    /**
     * Display the leaderboard with user rankings.
     */
    public function index()
    {
        $currentUser = auth()->user();
        
        // Get all users with their total points
        $users = User::withSum('pointsEvents', 'points')
            ->with('settings')
            ->orderBy('points_events_sum_points', 'desc')
            ->get();
        
        // Format leaderboard data
        $leaderboard = $users->map(function($user, $index) use ($currentUser) {
            $badges = $user->badges->pluck('name')->toArray();
            
            // Check if user opted out of leaderboard
            $settings = $user->settings;
            $isOptedOut = $settings && $settings->leaderboard_opt_out;
            
            return [
                'rank' => $index + 1,
                'name' => $isOptedOut ? 'Anonymous User' : $user->name,
                'points' => $user->points_events_sum_points ?? 0,
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($isOptedOut ? 'Anonymous' : $user->name) . '&background=' . ($index < 3 ? '059669' : 'random') . '&color=fff',
                'badges' => $badges,
                'is_me' => $user->id === $currentUser->id
            ];
        })->take(50); // Show top 50

        // Find current user's rank if not in top 50
        $currentUserRank = $users->search(function($user) use ($currentUser) {
            return $user->id === $currentUser->id;
        });

        return Inertia::render('Leaderboard/Index', [
            'leaderboard' => $leaderboard,
            'currentUserRank' => $currentUserRank !== false ? $currentUserRank + 1 : null
        ]);
    }
}

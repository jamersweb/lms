<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PointsEvent;
use App\Models\UserSettings;

class LeaderboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_leaderboard()
    {
        $user = User::factory()->create(['name' => 'Alice']);
        PointsEvent::factory()->create(['user_id' => $user->id, 'points' => 100]);
        
        $response = $this->actingAs($user)->get(route('leaderboard.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Leaderboard/Index')
            ->has('leaderboard', 1)
            ->where('leaderboard.0.name', 'Alice')
            ->where('leaderboard.0.points', 100)
            ->where('leaderboard.0.rank', 1)
        );
    }

    public function test_opt_out_user_is_anonymous()
    {
        $user = User::factory()->create(['name' => 'Bob']);
        UserSettings::create(['user_id' => $user->id, 'leaderboard_opt_out' => true]);
        PointsEvent::factory()->create(['user_id' => $user->id, 'points' => 200]);

        $viewer = User::factory()->create();
        
        $response = $this->actingAs($viewer)->get(route('leaderboard.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('leaderboard.0.name', 'Anonymous User')
            ->where('leaderboard.0.points', 200)
        );
    }
}

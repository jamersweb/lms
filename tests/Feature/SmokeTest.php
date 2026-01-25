<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_courses_index_loads()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/courses');
        $response->assertStatus(200);
    }

    public function test_habits_index_loads()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/habits');
        $response->assertStatus(200);
    }

    public function test_journal_index_loads()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/journal');
        $response->assertStatus(200);
    }

    public function test_notes_index_loads()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/notes');
        $response->assertStatus(200);
    }
    
    public function test_leaderboard_index_loads()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/leaderboard');
        $response->assertStatus(200);
    }
}

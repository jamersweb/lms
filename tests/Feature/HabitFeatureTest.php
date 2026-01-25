<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Habit;
use App\Models\HabitLog;
use Carbon\Carbon;

class HabitFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_habit()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('habits.store'), [
            'title' => 'Read Quran',
            'frequency_type' => 'daily',
            'target_per_day' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('habits', [
            'user_id' => $user->id,
            'title' => 'Read Quran',
        ]);
    }

    public function test_can_log_habit_progress()
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('habits.log', $habit), [
            'date' => Carbon::today()->toDateString(),
            'status' => 'done',
            'count' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('habit_logs', [
            'habit_id' => $habit->id,
            'status' => 'done',
            'completed_count' => 1,
        ]);
    }
    
    public function test_cannot_update_others_habit()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $userA->id]);
        
        $response = $this->actingAs($userB)->put(route('habits.update', $habit), [
            'title' => 'Hacked',
        ]);
        
        $response->assertForbidden();
    }
}

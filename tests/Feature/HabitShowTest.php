<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Habit;
use App\Models\HabitLog;

class HabitShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_habit_history()
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);
        HabitLog::factory()->create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->get(route('habits.show', $habit));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Habits/Show')
            ->has('habit')
            ->has('logs')
            ->has('streaks')
        );
    }
}

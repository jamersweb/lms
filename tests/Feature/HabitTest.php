<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_create_habits(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/habits', [
            'title' => 'Morning Adhkar',
            'description' => 'After Fajr prayer',
            'frequency_type' => 'daily',
            'target_per_day' => 1
        ]);

        $this->assertDatabaseHas('habits', [
            'user_id' => $user->id,
            'title' => 'Morning Adhkar',
        ]);

        $response->assertRedirect('/habits');
    }

    public function test_users_can_log_habit_completion(): void
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/habits/{$habit->id}/log");

        $this->assertDatabaseHas('habit_logs', [
            'user_id' => $user->id,
            'habit_id' => $habit->id,
        ]);

        $response->assertRedirect();
    }

    public function test_users_cannot_log_habit_twice_same_day(): void
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        HabitLog::create([
            'user_id' => $user->id,
            'habit_id' => $habit->id,
            'log_date' => today(),
            'status' => 'done',
            'completed_count' => 1
        ]);

        $response = $this->actingAs($user)->post("/habits/{$habit->id}/log");

        $this->assertEquals(1, HabitLog::where('habit_id', $habit->id)
            ->whereDate('log_date', today())
            ->count());
    }

    public function test_habit_streak_is_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        // Log habit for last 5 consecutive days
        for ($i = 0; $i < 5; $i++) {
            HabitLog::create([
                'user_id' => $user->id,
                'habit_id' => $habit->id,
                'log_date' => now()->subDays($i)->toDateString(),
                'status' => 'done',
                'completed_count' => 1
            ]);
        }

        $response = $this->actingAs($user)->get('/habits');

        $response->assertInertia(fn ($page) => $page
            ->where('habits.0.current_streak', 5)
        );
    }

    public function test_users_can_only_manage_their_own_habits(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->delete("/habits/{$habit->id}");

        $response->assertStatus(403);
    }

    public function test_users_can_update_habits(): void
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/habits/{$habit->id}", [
            'title' => 'Updated Habit',
            'description' => 'New description',
            'frequency_type' => 'daily',
            'target_per_day' => 2,
            'is_active' => true
        ]);

        $this->assertDatabaseHas('habits', [
            'id' => $habit->id,
            'title' => 'Updated Habit',
        ]);

        $response->assertRedirect('/habits');
    }

    public function test_users_can_delete_habits(): void
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/habits/{$habit->id}");

        $this->assertDatabaseMissing('habits', [
            'id' => $habit->id,
        ]);

        $response->assertRedirect('/habits');
    }
}

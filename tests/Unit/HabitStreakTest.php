<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\User;
use App\Services\HabitStreakService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HabitStreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_current_streak_correctly()
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        // Log past 3 days as done
        HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'log_date' => Carbon::yesterday()->subDay(), // 2 days ago
            'status' => 'done',
        ]);
        HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'log_date' => Carbon::yesterday(), // 1 day ago
            'status' => 'done',
        ]);
        HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'log_date' => Carbon::today(), // today
            'status' => 'done',
        ]);

        $service = new HabitStreakService();
        $streaks = $service->getStreaks($habit);

        $this->assertEquals(3, $streaks['current']);
        $this->assertEquals(3, $streaks['longest']);
    }

    public function test_streak_breaks_if_day_skipped()
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        // Yesterday done, Today done, but 2 days ago missing
        HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'log_date' => Carbon::yesterday(),
            'status' => 'done',
        ]);
        HabitLog::create([
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'log_date' => Carbon::today(),
            'status' => 'done',
        ]);

        $service = new HabitStreakService();
        $streaks = $service->getStreaks($habit);

        $this->assertEquals(2, $streaks['current']);
    }
    
    public function test_streak_zero_if_today_and_yesterday_missing()
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        HabitLog::create([
             'habit_id' => $habit->id,
             'user_id' => $user->id,
             'log_date' => Carbon::today()->subDays(2),
             'status' => 'done',
        ]);

        $service = new HabitStreakService();
        $streaks = $service->getStreaks($habit);

        $this->assertEquals(0, $streaks['current']);
    }
}

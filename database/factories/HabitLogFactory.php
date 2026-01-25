<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HabitLog>
 */
class HabitLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'habit_id' => \App\Models\Habit::factory(),
            'user_id' => \App\Models\User::factory(),
            'log_date' => $this->faker->date(),
            'completed_count' => 1,
            'status' => 'done',
        ];
    }
}

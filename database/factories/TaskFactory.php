<?php

namespace Database\Factories;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(), 'instructions' => fake()->paragraph(),
            'type' => 'practice_streak', 'required_days' => 1, 'unlock_next_lesson' => true,
            'taskable_type' => Lesson::class, 'taskable_id' => Lesson::factory()];
    }
}

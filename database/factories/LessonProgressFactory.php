<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonProgressFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'lesson_id' => Lesson::factory(),
            'is_completed' => false, 'last_position_seconds' => 0, 'watched_seconds' => 0];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PointsEvent>
 */
class PointsEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->uuid(),
            'user_id' => \App\Models\User::factory(),
            'event_type' => $this->faker->randomElement(['lesson_completed', 'course_completed', 'habit_completed', 'discussion_created', 'reply_created']),
            'points' => $this->faker->randomElement([1, 2, 5, 10, 50]),
            'source_type' => $this->faker->randomElement(['lesson', 'course', 'habit', 'discussion']),
            'source_id' => $this->faker->numberBetween(1, 100),
            'description' => $this->faker->sentence(),
        ];
    }
}

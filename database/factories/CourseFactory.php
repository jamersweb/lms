<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->paragraph(),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'instructor' => $this->faker->name(),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'thumbnail' => $this->faker->imageUrl(640, 480, 'education'),
        ];
    }
}

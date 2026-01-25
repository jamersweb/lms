<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => \App\Models\Module::factory(),
            'title' => $this->faker->sentence(4),
            'slug' => $this->faker->slug(),
            'video_provider' => 'youtube',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_free_preview' => $this->faker->boolean(),
        ];
    }
}

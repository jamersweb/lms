<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'entry_date' => $this->faker->date(),
            'mood' => $this->faker->randomElement(['great', 'good', 'neutral', 'low']),
            'content' => $this->faker->paragraph(),
        ];
    }
}

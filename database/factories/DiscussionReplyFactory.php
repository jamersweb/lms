<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiscussionReply>
 */
class DiscussionReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discussion_id' => \App\Models\Discussion::factory(),
            'user_id' => \App\Models\User::factory(),
            'body' => $this->faker->paragraph(),
        ];
    }
}

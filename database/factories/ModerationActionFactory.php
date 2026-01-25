<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ModerationAction>
 */
class ModerationActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'moderator_id' => \App\Models\User::factory()->state(['is_admin' => true]),
            'target_type' => 'discussion',
            'target_id' => 1,
            'action' => 'delete',
            'reason' => $this->faker->sentence(),
        ];
    }
}

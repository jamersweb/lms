<?php

namespace Database\Factories;

use App\Models\ContentRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentRule>
 */
class ContentRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'min_level' => null,
            'requires_bayah' => false,
            'gender' => null,
        ];
    }

    /**
     * Set minimum level requirement.
     */
    public function withMinLevel(string $level): static
    {
        return $this->state(fn (array $attributes) => [
            'min_level' => $level,
        ]);
    }

    /**
     * Set bay'ah requirement.
     */
    public function requiresBayah(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_bayah' => true,
        ]);
    }

    /**
     * Set gender restriction.
     */
    public function forGender(string $gender): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => $gender,
        ]);
    }
}

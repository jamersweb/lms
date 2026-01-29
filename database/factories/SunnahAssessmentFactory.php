<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class SunnahAssessmentFactory extends Factory
{
    protected $model = \App\Models\SunnahAssessment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'questions' => [
                [
                    'key' => 'question_1',
                    'text' => $this->faker->sentence() . '?',
                    'module_id' => null,
                ],
            ],
            'is_active' => true,
        ];
    }
}

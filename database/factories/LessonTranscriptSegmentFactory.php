<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonTranscriptSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonTranscriptSegment>
 */
class LessonTranscriptSegmentFactory extends Factory
{
    protected $model = LessonTranscriptSegment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->numberBetween(0, 300);
        $end = $start + 30;

        return [
            'lesson_id' => Lesson::factory(),
            'start_seconds' => $start,
            'end_seconds' => $end,
            'text' => $this->faker->sentence(10),
        ];
    }
}


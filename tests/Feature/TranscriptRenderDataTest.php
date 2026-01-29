<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonTranscriptSegment;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscriptRenderDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_show_includes_ordered_transcript_segments(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
        ]);

        $seg1 = LessonTranscriptSegment::factory()->create([
            'lesson_id' => $lesson->id,
            'start_seconds' => 30,
            'end_seconds' => 40,
            'text' => 'Second segment',
        ]);

        $seg0 = LessonTranscriptSegment::factory()->create([
            'lesson_id' => $lesson->id,
            'start_seconds' => 10,
            'end_seconds' => 20,
            'text' => 'First segment',
        ]);

        $user = \App\Models\User::factory()->create();

        // Minimal enrollment/progress to satisfy gating / progress logic
        \App\Models\Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        \App\Models\LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson->id]));

        $response->assertOk();

        $response->assertInertia(function ($page) use ($seg0, $seg1) {
            $page->component('Lessons/Show')
                ->has('lesson.transcript_segments');

            $segments = $page->toArray()['props']['lesson']['transcript_segments'];

            $this->assertCount(2, $segments);
            $this->assertEquals($seg0->id, $segments[0]['id']);
            $this->assertEquals($seg1->id, $segments[1]['id']);
        });
    }
}

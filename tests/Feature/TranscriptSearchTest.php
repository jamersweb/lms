<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonTranscriptSegment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscriptSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_transcript_search_returns_grouped_matches(): void
    {
        $user = User::factory()->create();

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Searchable Lesson',
        ]);

        LessonTranscriptSegment::factory()->create([
            'lesson_id' => $lesson->id,
            'start_seconds' => 5,
            'end_seconds' => 10,
            'text' => 'This is a special keyword segment.',
        ]);

        $response = $this->actingAs($user)
            ->get('/search?q=keyword');

        $response->assertOk();

        $response->assertInertia(function ($page) use ($lesson, $course) {
            $page->component('Search/Index')
                ->where('query', 'keyword')
                ->has('results');

            $props = $page->toArray()['props'];
            $result = collect($props['results'])->firstWhere('lesson_id', $lesson->id);

            $this->assertNotNull($result);
            $this->assertEquals($course->id, $result['course_id']);
            $this->assertNotEmpty($result['matches']);
            $this->assertEquals(5, $result['matches'][0]['start_seconds']);
        });
    }
}


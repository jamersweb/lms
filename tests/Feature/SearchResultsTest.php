<?php

namespace Tests\Feature;

use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class SearchResultsTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    public function test_searching_transcript_text_returns_results_with_timestamp(): void
    {
        $this->seed();
        $this->loginAsStudentUmar();

        $response = $this->get('/search?q=transcript');

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->component('Search/Index')
                ->where('query', 'transcript')
                ->has('results')
                ->has('results.0.matches')
                ->where('results.0.lesson_title', 'Demo Lesson 1');

            $matches = $page->toArray()['props']['results'][0]['matches'];
            $this->assertGreaterThan(0, count($matches));
            $this->assertSame(0, $matches[0]['start_seconds']);
        });
    }

    public function test_searching_lesson_title_returns_result(): void
    {
        $this->seed();
        $this->loginAsStudentUmar();

        $lesson2 = Lesson::where('slug', 'demo-lesson-2')->firstOrFail();

        $response = $this->get('/search?q=Demo%20Lesson%202');

        $response->assertOk();
        $response->assertInertia(function ($page) use ($lesson2) {
            $page->component('Search/Index')
                ->where('query', 'Demo Lesson 2')
                ->has('results', 1)
                ->where('results.0.lesson_id', $lesson2->id)
                ->has('results.0.matches');

            $matches = $page->toArray()['props']['results'][0]['matches'];
            $this->assertGreaterThan(0, count($matches));
            $this->assertSame(0, $matches[0]['start_seconds']);
        });
    }
}


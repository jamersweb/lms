<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class GatingRulesTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    public function test_female_cannot_open_male_only_lesson(): void
    {
        $this->seed();

        $female = $this->loginAsStudentFatima();
        $female->update(['gender' => 'female', 'has_bayah' => false, 'level' => 'beginner']);

        $course = Course::where('slug', 'demo-course')->firstOrFail();
        $lesson = Lesson::where('slug', 'demo-lesson-1')->firstOrFail();

        $lesson->update([
            'allowed_gender' => 'male',
            'requires_bayah' => false,
            'min_level' => 'beginner',
        ]);

        $response = $this->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson->id]));

        $response->assertStatus(403);
        $response->assertInertia(fn ($page) => $page->component('Errors/ForbiddenContent'));
    }

    public function test_no_bayah_cannot_open_requires_bayah_lesson(): void
    {
        $this->seed();

        $user = $this->loginAsStudentUmar();
        $user->update(['gender' => 'male', 'has_bayah' => false, 'level' => 'beginner']);

        $course = Course::where('slug', 'demo-course')->firstOrFail();
        $lesson = Lesson::where('slug', 'demo-lesson-2')->firstOrFail();

        $lesson->update([
            'allowed_gender' => 'all',
            'requires_bayah' => true,
            'min_level' => 'beginner',
        ]);

        $response = $this->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson->id]));

        $response->assertStatus(403);
        $response->assertInertia(fn ($page) => $page->component('Errors/ForbiddenContent'));
    }

    public function test_beginner_cannot_open_min_level_intermediate_lesson(): void
    {
        $this->seed();

        $user = $this->loginAsStudentUmar();
        $user->update(['gender' => 'male', 'has_bayah' => true, 'level' => 'beginner']);

        $course = Course::where('slug', 'demo-course')->firstOrFail();
        $lesson = Lesson::where('slug', 'demo-lesson-3')->firstOrFail();

        $lesson->update([
            'allowed_gender' => 'all',
            'requires_bayah' => false,
            'min_level' => 'intermediate',
        ]);

        $response = $this->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson->id]));

        $response->assertStatus(403);
        $response->assertInertia(fn ($page) => $page->component('Errors/ForbiddenContent'));
    }
}


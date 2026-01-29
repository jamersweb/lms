<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithDemoUsers;
use Tests\TestCase;

class LessonProgressUiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithDemoUsers;

    public function test_mark_complete_then_playlist_shows_completed(): void
    {
        $this->seed();

        $user = $this->loginAsStudentUmar();

        $course = Course::where('slug', 'demo-course')->firstOrFail();
        $lesson = Lesson::where('slug', 'demo-lesson-1')->firstOrFail();

        // Ensure watch tracking satisfies verification
        \App\Models\LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'time_watched_seconds' => 600,
                'max_playback_rate_seen' => 1.0,
                'seek_detected' => false,
            ]
        );

        // Ensure enrollment exists (seed should already create this, but keep deterministic)
        Enrollment::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'enrolled_at' => now(),
            ]
        );

        // Initial load: lesson not completed in playlist
        $response = $this->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Lessons/Show')
            ->where('playlist.0.id', $lesson->id)
            ->where('playlist.0.is_completed', false)
        );

        // Mark complete
        $this->post("/lessons/{$lesson->id}/complete")->assertRedirect();

        // Reload and assert playlist reflects completion
        $response = $this->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Lessons/Show')
            ->where('playlist.0.id', $lesson->id)
            ->where('playlist.0.is_completed', true)
        );
    }
}


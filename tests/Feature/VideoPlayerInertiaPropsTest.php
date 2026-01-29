<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPlayerInertiaPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_show_contains_video_provider_and_url(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'video_provider' => 'youtube',
            'youtube_video_id' => 'ABC123XYZ',
        ]);

        $user = User::factory()->create();

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

        $response->assertInertia(function ($page) use ($lesson) {
            $page->component('Lessons/Show')
                ->where('lesson.video_provider', 'youtube')
                ->where('lesson.video_url', $lesson->video_url);
        });
    }
}


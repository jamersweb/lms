<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonTranscriptSegment;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase0RegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_youtube_video_url_uses_nocookie_domain(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'video_provider' => 'youtube',
            'youtube_video_id' => 'ABC123',
        ]);

        $this->assertStringContainsString(
            'https://www.youtube-nocookie.com/embed/ABC123',
            $lesson->video_url
        );
    }

    public function test_mp4_video_url_uses_storage_url(): void
    {
        Storage::fake('public');

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'video_provider' => 'mp4',
            'video_path' => 'videos/test.mp4',
        ]);

        $this->assertStringContainsString('/storage/', $lesson->video_url);
        $this->assertStringContainsString('videos/test.mp4', $lesson->video_url);
    }

    public function test_transcript_text_concatenates_segments(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        LessonTranscriptSegment::factory()->create([
            'lesson_id' => $lesson->id,
            'start_seconds' => 0,
            'end_seconds' => 10,
            'text' => 'First line',
        ]);

        LessonTranscriptSegment::factory()->create([
            'lesson_id' => $lesson->id,
            'start_seconds' => 10,
            'end_seconds' => 20,
            'text' => 'Second line',
        ]);

        $lesson->refresh();

        $this->assertStringContainsString('First line', $lesson->transcript_text);
        $this->assertStringContainsString('Second line', $lesson->transcript_text);
    }

    public function test_lesson_show_includes_transcript_data_for_enrolled_user(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'video_provider' => 'youtube',
            'youtube_video_id' => 'ABC123',
        ]);

        LessonTranscriptSegment::factory()->count(2)->create([
            'lesson_id' => $lesson->id,
        ]);

        $user = \App\Models\User::factory()->create();

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        // Minimal progress row so journey/progress code has something to work with
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson->id]));

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Lessons/Show')
            ->has('lesson.transcript_text')
            ->has('lesson.transcript_segments')
        );
    }
}

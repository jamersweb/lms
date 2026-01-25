<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;

class UploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_valid_mp4_and_transcript()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $vttContent = "WEBVTT\n\n00:00:01.000 --> 00:00:04.000\nHello World";

        $response = $this->actingAs($admin)->post(route('admin.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'Video Lesson',
            'slug' => 'video-lesson',
            'video_provider' => 'mp4',
            'video_file' => UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4'),
            'transcript_file' => UploadedFile::fake()->createWithContent('subtitles.vtt', $vttContent),
            'is_free_preview' => false,
        ]);

        $response->assertRedirect();
        
        $lesson = Lesson::where('slug', 'video-lesson')->first();
        $this->assertNotNull($lesson);
        $this->assertNotNull($lesson->video_path);
        
        Storage::disk('public')->assertExists($lesson->video_path);
        
        // Check transcript segments
        $this->assertDatabaseHas('lesson_transcript_segments', [
            'lesson_id' => $lesson->id,
            'text' => 'Hello World',
            'start_seconds' => 1.0,
        ]);
    }

    public function test_mp4_upload_limit()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        // 600MB file (limit is 500MB) -> 600 * 1024 = 614400 KB
        $response = $this->actingAs($admin)->post(route('admin.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'Big Video',
            'slug' => 'big-video',
            'video_provider' => 'mp4',
            'video_file' => UploadedFile::fake()->create('big.mp4', 614400, 'video/mp4'),
        ]);

        $response->assertSessionHasErrors('video_file');
    }

    public function test_invalid_transcript_type()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($admin)->post(route('admin.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'Bad Transcript',
            'slug' => 'bad-transcript',
            'video_provider' => 'youtube',
            'youtube_video_id' => '123',
            'transcript_file' => UploadedFile::fake()->create('malicious.php', 10, 'text/x-php'),
        ]);

        $response->assertSessionHasErrors('transcript_file');
    }
}

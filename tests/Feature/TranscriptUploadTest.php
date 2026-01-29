<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonTranscriptSegment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TranscriptUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_admin_can_upload_transcript_on_create(): void
    {
        Storage::fake('public');

        $admin = $this->makeAdmin();

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $vtt = <<<VTT
WEBVTT

00:00:00.000 --> 00:00:02.000
First line

00:00:02.000 --> 00:00:04.000
Second line
VTT;

        $file = UploadedFile::fake()->createWithContent('test.vtt', $vtt);

        $response = $this->actingAs($admin)
            ->post('/admin/lessons', [
                'module_id' => $module->id,
                'title' => 'With Transcript',
                'slug' => 'with-transcript',
                'video_provider' => 'youtube',
                'youtube_video_id' => 'ABC123',
                'sort_order' => 0,
                'is_free_preview' => false,
                'transcript_file' => $file,
            ]);

        $response->assertRedirect('/admin/lessons');

        $lesson = Lesson::where('slug', 'with-transcript')->firstOrFail();

        $this->assertDatabaseHas('lesson_transcript_segments', [
            'lesson_id' => $lesson->id,
        ]);

        $this->assertSame(2, LessonTranscriptSegment::where('lesson_id', $lesson->id)->count());
    }
}


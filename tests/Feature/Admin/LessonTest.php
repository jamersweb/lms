<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_lesson_with_youtube()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($admin)->post(route('admin.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'YT Lesson',
            'slug' => 'yt-lesson',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'abc12345',
            'sort_order' => 1,
            'is_free_preview' => false,
        ]);

        $response->assertRedirect(route('admin.lessons.index'));
        $this->assertDatabaseHas('lessons', [
            'title' => 'YT Lesson',
            'video_provider' => 'youtube',
            'youtube_video_id' => 'abc12345',
        ]);
    }

    public function test_admin_can_create_lesson_with_mp4()
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($admin)->post(route('admin.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'MP4 Lesson',
            'slug' => 'mp4-lesson',
            'video_provider' => 'mp4',
            'video_file' => $file,
            'sort_order' => 2,
            'is_free_preview' => true,
        ]);

        $response->assertRedirect(route('admin.lessons.index'));
        $this->assertDatabaseHas('lessons', [
            'title' => 'MP4 Lesson',
            'video_provider' => 'mp4',
        ]);
        
        $lesson = \App\Models\Lesson::where('slug', 'mp4-lesson')->first();
        Storage::disk('public')->assertExists($lesson->video_path);
    }
    
    public function test_validation_fails_if_youtube_id_missing()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $response = $this->actingAs($admin)->post(route('admin.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'Invalid Lesson',
            'slug' => 'invalid-lesson',
            'video_provider' => 'youtube',
        ]);
        
        $response->assertSessionHasErrors('youtube_video_id');
    }
}

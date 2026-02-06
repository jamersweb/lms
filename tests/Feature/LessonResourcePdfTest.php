<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonResourcePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_download_pdf()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        
        // Create resource
        $resource = LessonResource::factory()->create([
            'lesson_id' => $lesson->id,
            'sunnah_pointers' => 'Test sunnah pointers',
            'duas_text' => 'Test duas text',
        ]);

        // Enroll user
        $user->enrollments()->create(['course_id' => $course->id]);

        // Complete lesson
        LessonProgress::factory()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lessons.resources.pdf', $lesson));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('PDF', $response->getContent());
    }

    public function test_authorized_user_can_view_pdf_inline()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        
        // Create resource
        $resource = LessonResource::factory()->create([
            'lesson_id' => $lesson->id,
            'sunnah_pointers' => 'Test sunnah pointers',
            'duas_text' => 'Test duas text',
        ]);

        // Enroll user
        $user->enrollments()->create(['course_id' => $course->id]);

        // Complete lesson
        LessonProgress::factory()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lessons.resources.pdf.view', $lesson));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', function ($value) {
            return str_contains($value, 'inline');
        });
        $this->assertStringContainsString('PDF', $response->getContent());
    }

    public function test_unauthorized_user_cannot_access_pdf()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        
        // Create resource
        $resource = LessonResource::factory()->create([
            'lesson_id' => $lesson->id,
            'sunnah_pointers' => 'Test sunnah pointers',
            'duas_text' => 'Test duas text',
        ]);

        // User NOT enrolled
        // No enrollment created

        $response = $this->actingAs($user)->get(route('lessons.resources.pdf', $lesson));

        $response->assertStatus(403);
    }

    public function test_user_must_complete_lesson_before_accessing_pdf()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        
        // Create resource
        $resource = LessonResource::factory()->create([
            'lesson_id' => $lesson->id,
            'sunnah_pointers' => 'Test sunnah pointers',
            'duas_text' => 'Test duas text',
        ]);

        // Enroll user
        $user->enrollments()->create(['course_id' => $course->id]);

        // Lesson NOT completed
        // No lesson progress created

        $response = $this->actingAs($user)->get(route('lessons.resources.pdf', $lesson));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_pdf()
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $response = $this->get(route('lessons.resources.pdf', $lesson));

        $response->assertRedirect(route('login'));
    }

    public function test_pdf_returns_404_when_no_resource_exists()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        
        // No resource created

        // Enroll user
        $user->enrollments()->create(['course_id' => $course->id]);

        // Complete lesson
        LessonProgress::factory()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lessons.resources.pdf', $lesson));

        $response->assertStatus(404);
    }

    public function test_pdf_download_has_correct_content_type()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        
        $resource = LessonResource::factory()->create([
            'lesson_id' => $lesson->id,
            'sunnah_pointers' => 'Test content',
            'duas_text' => 'Test duas',
        ]);

        $user->enrollments()->create(['course_id' => $course->id]);
        LessonProgress::factory()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lessons.resources.pdf', $lesson));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_view_has_inline_disposition()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        
        $resource = LessonResource::factory()->create([
            'lesson_id' => $lesson->id,
            'sunnah_pointers' => 'Test content',
            'duas_text' => 'Test duas',
        ]);

        $user->enrollments()->create(['course_id' => $course->id]);
        LessonProgress::factory()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('lessons.resources.pdf.view', $lesson));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('filename', $disposition);
    }
}

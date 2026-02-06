<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonVideoProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonVideoProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_video_progress()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'video_provider' => 'youtube',
            'youtube_video_id' => 'test123',
        ]);

        // Enroll user
        $user->enrollments()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user)->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
            'duration_seconds' => 600,
            'last_position_seconds' => 120,
            'percent_complete' => 20,
            'provider' => 'youtube',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('lesson_video_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'duration_seconds' => 600,
            'last_position_seconds' => 120,
            'percent_complete' => 20.0,
            'is_completed' => false,
        ]);
    }

    public function test_can_update_video_progress()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        // Create initial progress
        $progress = LessonVideoProgress::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'duration_seconds' => 600,
            'last_position_seconds' => 120,
            'percent_complete' => 20,
            'provider' => 'youtube',
        ]);

        // Update progress
        $response = $this->actingAs($user)->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
            'duration_seconds' => 600,
            'last_position_seconds' => 300,
            'percent_complete' => 50,
            'provider' => 'youtube',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('lesson_video_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'last_position_seconds' => 300,
            'percent_complete' => 50.0,
        ]);

        // Should still be same record (updateOrCreate)
        $this->assertEquals(1, LessonVideoProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->count());
    }

    public function test_clamps_last_position_to_duration()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user)->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
            'duration_seconds' => 600,
            'last_position_seconds' => 800, // Exceeds duration
            'percent_complete' => 50,
            'provider' => 'youtube',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('lesson_video_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'last_position_seconds' => 600, // Clamped to duration
        ]);
    }

    public function test_auto_completes_at_95_percent()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user)->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
            'duration_seconds' => 600,
            'last_position_seconds' => 570, // 95%
            'percent_complete' => 95,
            'provider' => 'youtube',
        ]);

        $response->assertStatus(200);

        $progress = LessonVideoProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $this->assertTrue($progress->is_completed);
        $this->assertNotNull($progress->completed_at);
        $this->assertEquals(100.0, $progress->percent_complete);
        $this->assertEquals(600, $progress->last_position_seconds); // Set to end
    }

    public function test_auto_completes_when_video_ended()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        // Position at end (duration - 1 second)
        $response = $this->actingAs($user)->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
            'duration_seconds' => 600,
            'last_position_seconds' => 599, // At end
            'percent_complete' => 99.8,
            'provider' => 'youtube',
        ]);

        $response->assertStatus(200);

        $progress = LessonVideoProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $this->assertTrue($progress->is_completed);
        $this->assertNotNull($progress->completed_at);
    }

    public function test_can_get_video_progress()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        $progress = LessonVideoProgress::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'duration_seconds' => 600,
            'last_position_seconds' => 120,
            'percent_complete' => 20,
            'provider' => 'youtube',
        ]);

        $response = $this->actingAs($user)->getJson(route('lesson-progress.show', ['lesson' => $lesson->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'last_position_seconds' => 120,
            'percent_complete' => 20.0,
            'is_completed' => false,
        ]);
    }

    public function test_returns_default_values_when_no_progress_exists()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user)->getJson(route('lesson-progress.show', ['lesson' => $lesson->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'last_position_seconds' => 0,
            'percent_complete' => 0,
            'is_completed' => false,
        ]);
    }

    public function test_requires_authentication()
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $response = $this->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
            'duration_seconds' => 600,
            'last_position_seconds' => 120,
            'percent_complete' => 20,
        ]);

        $response->assertStatus(401);
    }

    public function test_requires_enrollment()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        // User not enrolled

        $response = $this->actingAs($user)->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
            'duration_seconds' => 600,
            'last_position_seconds' => 120,
            'percent_complete' => 20,
        ]);

        $response->assertStatus(403);
    }

    public function test_validates_input()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        // Missing required fields
        $response = $this->actingAs($user)->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['duration_seconds', 'last_position_seconds', 'percent_complete']);

        // Invalid percent (> 100)
        $response = $this->actingAs($user)->postJson(route('lesson-progress.update', ['lesson' => $lesson->id]), [
            'duration_seconds' => 600,
            'last_position_seconds' => 120,
            'percent_complete' => 150, // Invalid
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['percent_complete']);
    }

    public function test_completed_video_does_not_resume()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        // Create completed progress
        $progress = LessonVideoProgress::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'duration_seconds' => 600,
            'last_position_seconds' => 600,
            'percent_complete' => 100,
            'is_completed' => true,
            'completed_at' => now(),
            'provider' => 'youtube',
        ]);

        $response = $this->actingAs($user)->getJson(route('lesson-progress.show', ['lesson' => $lesson->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'is_completed' => true,
        ]);

        // Frontend should check is_completed and not resume
    }
}

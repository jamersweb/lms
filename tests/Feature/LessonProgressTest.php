<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_users_can_mark_lessons_complete(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'video_duration_seconds' => 10]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()
        ]);

        // Satisfy verification conditions
        \App\Models\LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 10,
            'max_playback_rate_seen' => 1.0,
            'seek_detected' => false,
        ]);

        $response = $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $response->assertRedirect();
    }

    public function test_completing_lesson_awards_points(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'video_duration_seconds' => 10]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()
        ]);

        \App\Models\LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 10,
            'max_playback_rate_seen' => 1.0,
            'seek_detected' => false,
        ]);

        $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");

        $this->assertDatabaseHas('points_events', [
            'user_id' => $user->id,
            'event_type' => 'lesson_completed',
            'points' => 10
        ]);
    }

    public function test_completing_all_lessons_awards_course_completion_bonus(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(3)->create(['module_id' => $module->id, 'video_duration_seconds' => 10]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()
        ]);

        foreach ($lessons as $lesson) {
            \App\Models\LessonProgress::create([
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'time_watched_seconds' => 10,
                'max_playback_rate_seen' => 1.0,
                'seek_detected' => false,
            ]);
        }

        // Complete first 2 lessons
        foreach ($lessons->take(2) as $lesson) {
            $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");
        }

        // Complete last lesson (should trigger course completion)
        $response = $this->actingAs($user)->post("/lessons/{$lessons->last()->id}/complete");

        $this->assertDatabaseHas('points_events', [
            'user_id' => $user->id,
            'event_type' => 'course_completed',
            'points' => 50
        ]);
    }

    public function test_non_enrolled_users_cannot_mark_lessons_complete(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $response = $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");

        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_cannot_complete_same_lesson_twice(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()
        ]);

        $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");
        $this->actingAs($user)->post("/lessons/{$lesson->id}/complete");

        $this->assertEquals(1, $user->lessonProgress()
            ->where('lesson_id', $lesson->id)
            ->whereNotNull('completed_at')
            ->count());
    }
}

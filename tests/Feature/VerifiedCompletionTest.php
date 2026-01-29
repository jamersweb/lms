<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserCourseLesson(int $duration = 100): array
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'video_duration_seconds' => $duration,
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return [$user, $course, $lesson];
    }

    public function test_cannot_complete_without_enough_watch_time(): void
    {
        [$user, $course, $lesson] = $this->setupUserCourseLesson(100);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 50,
            'max_playback_rate_seen' => 1.0,
            'seek_detected' => false,
        ]);

        $response = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/complete");

        $response->assertStatus(422);
        $response->assertJsonFragment(['ok' => false]);
        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'verified_completion' => true,
        ]);
    }

    public function test_cannot_complete_if_seek_detected_true(): void
    {
        [$user, $course, $lesson] = $this->setupUserCourseLesson(100);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 98,
            'max_playback_rate_seen' => 1.0,
            'seek_detected' => true,
        ]);

        $response = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/complete");

        $response->assertStatus(422);
        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'verified_completion' => true,
        ]);
    }

    public function test_cannot_complete_if_playback_rate_too_high(): void
    {
        [$user, $course, $lesson] = $this->setupUserCourseLesson(100);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 98,
            'max_playback_rate_seen' => 2.0,
            'seek_detected' => false,
        ]);

        $response = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/complete");

        $response->assertStatus(422);
        $this->assertDatabaseMissing('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'verified_completion' => true,
        ]);
    }

    public function test_can_complete_when_conditions_met(): void
    {
        [$user, $course, $lesson] = $this->setupUserCourseLesson(100);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 96, // >= duration - 5
            'max_playback_rate_seen' => 1.5,
            'seek_detected' => false,
        ]);

        $response = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/complete");

        $response->assertSuccessful();

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
            'verified_completion' => true,
        ]);
    }
}


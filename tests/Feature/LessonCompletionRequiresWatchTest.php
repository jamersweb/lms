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

class LessonCompletionRequiresWatchTest extends TestCase
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

    public function test_completion_fails_when_below_90_percent_watch_time(): void
    {
        [$user, $course, $lesson] = $this->setupUserCourseLesson(100);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 80, // below 90%
            'max_playback_rate_seen' => 1.0,
            'seek_detected' => false,
        ]);

        $response = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/complete");

        $response->assertStatus(422);
        $response->assertJsonFragment(['ok' => false]);
    }

    public function test_completion_succeeds_when_at_least_90_percent_watched_and_rate_ok(): void
    {
        [$user, $course, $lesson] = $this->setupUserCourseLesson(100);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 95, // >= 90%
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

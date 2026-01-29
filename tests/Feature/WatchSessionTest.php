<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function createEnrolledUserAndLesson(): array
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_free_preview' => true]);

        // Mark user as enrolled to satisfy checks
        $user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return [$user, $course, $lesson];
    }

    public function test_start_creates_session(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $response = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/start");

        $response->assertOk();
        $sessionId = $response->json('session_id');
        $this->assertNotNull($sessionId);

        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_heartbeat_accumulates_watch_time(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/start");
        $sessionId = $start->json('session_id');

        $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/heartbeat", [
            'session_id' => $sessionId,
            'current_time' => 10,
            'playback_rate' => 1.0,
        ])->assertOk();

        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'watch_time_seconds' => 10,
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_seek_jump_sets_seek_detected_true(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/start");
        $sessionId = $start->json('session_id');

        $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/heartbeat", [
            'session_id' => $sessionId,
            'current_time' => 5,
            'playback_rate' => 1.0,
        ])->assertOk();

        // big jump forward (> heartbeat interval + 4s)
        $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/heartbeat", [
            'session_id' => $sessionId,
            'current_time' => 25,
            'playback_rate' => 1.0,
        ])->assertOk();

        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'seek_events_count' => 1,
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'seek_detected' => true,
        ]);
    }

    public function test_max_playback_rate_seen_updates(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/start");
        $sessionId = $start->json('session_id');

        $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/heartbeat", [
            'session_id' => $sessionId,
            'current_time' => 10,
            'playback_rate' => 1.0,
        ])->assertOk();

        $this->actingAs($user)->postJson("/lessons/{$lesson->id}/watch/heartbeat", [
            'session_id' => $sessionId,
            'current_time' => 20,
            'playback_rate' => 2.0,
        ])->assertOk();

        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'max_playback_rate' => 2.0,
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'max_playback_rate_seen' => 2.0,
        ]);
    }
}


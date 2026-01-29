<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoWatchSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function createCourseLessonAndUsers(): array
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $enrolled = User::factory()->create();
        $unenrolled = User::factory()->create();

        $enrolled->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return [$course, $lesson, $enrolled, $unenrolled];
    }

    public function test_enrolled_user_can_start_session(): void
    {
        [$course, $lesson, $enrolled, $unenrolled] = $this->createCourseLessonAndUsers();

        $response = $this->actingAs($enrolled)->postJson(route('lessons.watch.start', $lesson));

        $response->assertOk();
        $sessionId = $response->json('session_id');
        $this->assertNotNull($sessionId);

        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'user_id' => $enrolled->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_non_enrolled_user_cannot_start_session(): void
    {
        [$course, $lesson, $enrolled, $unenrolled] = $this->createCourseLessonAndUsers();

        $response = $this->actingAs($unenrolled)->postJson(route('lessons.watch.start', $lesson));

        $response->assertStatus(403);
    }

    public function test_heartbeat_increments_watch_time_and_seek_violations(): void
    {
        [$course, $lesson, $enrolled, $unenrolled] = $this->createCourseLessonAndUsers();

        $start = $this->actingAs($enrolled)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // First heartbeat at 5s
        $this->actingAs($enrolled)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'current_time' => 5,
            'playback_rate' => 1.0,
        ])->assertOk();

        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'watch_time_seconds' => 5,
        ]);

        // Large forward jump to trigger seek detection
        $this->actingAs($enrolled)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'current_time' => 25,
            'playback_rate' => 1.0,
        ])->assertOk();

        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'seek_events_count' => 1,
        ]);
    }
}


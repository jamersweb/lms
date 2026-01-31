<?php

namespace Tests\Feature;

use App\Models\ContentRule;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonWatchSession;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2WatchTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function createEnrolledUserAndLesson(): array
    {
        $user = User::factory()->create([
            'level' => 'beginner',
            'has_bayah' => false,
            'gender' => 'male',
        ]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_free_preview' => true,
        ]);

        // Mark user as enrolled
        $user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return [$user, $course, $lesson];
    }

    public function test_start_creates_session_with_eligibility_check(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $response = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));

        $response->assertOk();
        $data = $response->json();
        $this->assertNotNull($data['session_id']);
        $this->assertNotNull($data['server_time']);
        $this->assertEquals(15, $data['heartbeat_interval']);

        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $data['session_id'],
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 0,
            'seek_attempts' => 0,
            'max_playback_rate' => 1.0,
        ]);
    }

    public function test_start_denies_access_when_eligibility_fails(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        // Create a rule that denies access (requires bay'ah)
        $lesson->contentRule()->create([
            'requires_bayah' => true,
            'min_level' => null,
            'gender' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));

        $response->assertStatus(403);
    }

    public function test_heartbeat_accumulates_watched_seconds(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // First heartbeat: position 10s, delta 10s
        $response = $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 10,
            'playback_rate' => 1.0,
            'played_delta_seconds' => 10,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'watched_seconds' => 10,
            'last_position_seconds' => 10,
        ]);

        // Second heartbeat: position 25s, delta 15s
        $response = $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 25,
            'playback_rate' => 1.0,
            'played_delta_seconds' => 15,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('lesson_watch_sessions', [
            'id' => $sessionId,
            'watched_seconds' => 25, // 10 + 15
            'last_position_seconds' => 25,
        ]);

        // Check lesson_progress also updated
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();
        $this->assertNotNull($progress);
        $this->assertEquals(25, $progress->watched_seconds);
        $this->assertEquals(25, $progress->last_position_seconds);
    }

    public function test_max_playback_rate_is_tracked(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // Heartbeat with rate 1.0
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 10,
            'playback_rate' => 1.0,
        ]);

        $session = LessonWatchSession::find($sessionId);
        $this->assertEquals(1.0, $session->max_playback_rate);

        // Heartbeat with rate 1.5
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 20,
            'playback_rate' => 1.5,
        ]);

        $session->refresh();
        $this->assertEquals(1.5, $session->max_playback_rate);

        // Heartbeat with rate 2.0 (should be recorded even if client clamps)
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 30,
            'playback_rate' => 2.0,
        ]);

        $session->refresh();
        $this->assertEquals(2.0, $session->max_playback_rate);

        // Check violation was recorded
        $violations = $session->violations ?? [];
        $rateViolations = array_filter($violations, fn($v) => $v['type'] === 'rate_exceeded');
        $this->assertNotEmpty($rateViolations);
    }

    public function test_seek_attempt_increments_and_records_violation(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // First heartbeat at 5s
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 5,
            'playback_rate' => 1.0,
        ]);

        // Large forward jump (> 20s threshold)
        $response = $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 30, // jump from 5 to 30 = 25s jump
            'playback_rate' => 1.0,
            'is_seeking' => false, // server should detect from position jump
        ]);

        $response->assertOk();
        $session = LessonWatchSession::find($sessionId);
        $this->assertEquals(1, $session->seek_attempts);

        // Check violation was recorded
        $violations = $session->violations ?? [];
        $seekViolations = array_filter($violations, fn($v) => $v['type'] === 'seek_forward');
        $this->assertNotEmpty($seekViolations);

        // Check lesson_progress also updated
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();
        $this->assertEquals(1, $progress->seek_attempts);
    }

    public function test_seek_detected_via_is_seeking_flag(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // Heartbeat with is_seeking=true
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 10,
            'playback_rate' => 1.0,
            'is_seeking' => true,
        ]);

        $session = LessonWatchSession::find($sessionId);
        $this->assertEquals(1, $session->seek_attempts);
    }

    public function test_authorization_prevents_other_user_heartbeat(): void
    {
        [$user1, $course, $lesson] = $this->createEnrolledUserAndLesson();
        $user2 = User::factory()->create();
        $user2->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        // User1 starts session
        $start = $this->actingAs($user1)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // User2 tries to send heartbeat with User1's session_id
        $response = $this->actingAs($user2)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 10,
            'playback_rate' => 1.0,
        ]);

        $response->assertStatus(404); // firstOrFail returns 404
    }

    public function test_heartbeat_delta_clamping(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // Send heartbeat with huge delta (should be clamped)
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 10,
            'playback_rate' => 1.0,
            'played_delta_seconds' => 1000, // Huge delta
        ]);

        $session = LessonWatchSession::find($sessionId);
        // Delta should be clamped to heartbeat_interval + 5 = 20 seconds
        $this->assertLessThanOrEqual(20, $session->watched_seconds);
    }

    public function test_visibility_tracking_records_violation(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // Heartbeat with hidden visibility
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 10,
            'playback_rate' => 1.0,
            'visibility' => 'hidden',
        ]);

        $session = LessonWatchSession::find($sessionId);
        $violations = $session->violations ?? [];
        $hiddenViolations = array_filter($violations, fn($v) => $v['type'] === 'tab_hidden');
        $this->assertNotEmpty($hiddenViolations);
    }

    public function test_end_session_marks_ended_at(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        $response = $this->actingAs($user)->postJson(route('lessons.watch.end', $lesson), [
            'session_id' => $sessionId,
        ]);

        $response->assertOk();
        $response->assertJson(['ended' => true]);

        $session = LessonWatchSession::find($sessionId);
        $this->assertNotNull($session->ended_at);
    }

    public function test_playback_rate_clamped_to_minimum_0_5(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson();

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // Send heartbeat with rate 0.25 (below minimum)
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 10,
            'playback_rate' => 0.25,
        ]);

        $session = LessonWatchSession::find($sessionId);
        // Should be clamped to 0.5
        $this->assertEquals(0.5, $session->max_playback_rate);
    }
}

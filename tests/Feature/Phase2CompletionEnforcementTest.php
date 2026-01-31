<?php

namespace Tests\Feature;

use App\Models\ContentRule;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2CompletionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function createEnrolledUserAndLesson(int $duration = 100): array
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
            'duration_seconds' => $duration,
            'is_free_preview' => true,
        ]);

        $user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return [$user, $course, $lesson];
    }

    public function test_denies_completion_if_watched_less_than_required(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(100);

        // Create progress with insufficient watch time (50s < 95s required)
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 50,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertStatus(422);
        $response->assertJson([
            'ok' => false,
        ]);
        $this->assertContains('insufficient_watch_time', $response->json('errors'));
    }

    public function test_allows_completion_if_watched_at_least_required(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(100);

        // Create progress with sufficient watch time (96s >= 95s required for 95% of 100s)
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 96,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
        ]);

        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();
        $this->assertNotNull($progress->completed_at);
        $this->assertNotNull($progress->completion_meta);
        $this->assertEquals(96, $progress->completion_meta['watched_seconds']);
    }

    public function test_denies_completion_if_max_playback_rate_exceeded(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(100);

        // Create progress with rate violation
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 96, // sufficient watch time
            'max_playback_rate' => 2.0, // exceeds 1.5
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertStatus(422);
        $this->assertContains('playback_rate_too_high', $response->json('errors'));
        $this->assertStringContainsString('Playback speed exceeded', $response->json('message'));
    }

    public function test_denies_completion_if_seek_attempts_exist(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(100);

        // Create progress with seek attempts
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 96,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 1,
            'violations' => [
                ['type' => 'seek_forward', 'at' => now()->toIso8601String()],
            ],
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertStatus(422);
        $this->assertContains('seek_detected', $response->json('errors'));
        $this->assertStringContainsString('Skipping ahead', $response->json('message'));
    }

    public function test_denies_completion_if_seek_forward_violation_exists(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(100);

        // Create progress with seek violation but no seek_attempts count
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 96,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [
                ['type' => 'seek_forward', 'at' => now()->toIso8601String()],
            ],
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertStatus(422);
        $this->assertContains('seek_detected', $response->json('errors'));
    }

    public function test_denies_completion_if_duration_missing_when_required(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(0);
        $lesson->duration_seconds = null;
        $lesson->save();

        // Config requires duration
        config(['video_guard.require_duration_for_completion' => true]);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertStatus(422);
        $this->assertContains('missing_duration', $response->json('errors'));
    }

    public function test_enforces_min_watch_seconds_threshold(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(20); // Very short video

        // Watch 19s (95% of 20s = 19s, but min_watch_seconds = 30)
        // So required should be 30s
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 19,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertStatus(422);
        $this->assertContains('insufficient_watch_time', $response->json('errors'));

        // Now watch 30s (meets min threshold)
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();
        $progress->watched_seconds = 30;
        $progress->save();

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));
        $response->assertOk();
    }

    public function test_completion_stores_metadata_snapshot(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(100);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 96,
            'max_playback_rate' => 1.5,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertOk();

        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $this->assertNotNull($progress->completion_meta);
        $meta = $progress->completion_meta;
        $this->assertEquals(96, $meta['watched_seconds']);
        $this->assertEquals(100, $meta['duration_seconds']);
        $this->assertEquals(1.5, $meta['max_playback_rate']);
        $this->assertEquals(0, $meta['seek_attempts']);
        $this->assertArrayHasKey('completed_at', $meta);
    }

    public function test_heartbeat_records_rate_violation(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(100);

        $start = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson));
        $sessionId = $start->json('session_id');

        // Send heartbeat with rate 2.0 (exceeds 1.5)
        $this->actingAs($user)->postJson(route('lessons.watch.heartbeat', $lesson), [
            'session_id' => $sessionId,
            'position_seconds' => 10,
            'playback_rate' => 2.0,
        ]);

        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $this->assertNotNull($progress);
        $this->assertEquals(2.0, $progress->max_playback_rate);

        // Check violation was recorded
        $violations = $progress->violations ?? [];
        $rateViolations = array_filter($violations, fn($v) => $v['type'] === 'rate_exceeded');
        $this->assertNotEmpty($rateViolations);
    }

    public function test_completion_checks_eligibility(): void
    {
        [$user, $course, $lesson] = $this->createEnrolledUserAndLesson(100);

        // Create rule that denies access
        $lesson->contentRule()->create([
            'requires_bayah' => true,
        ]);

        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 96,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('lessons.complete', $lesson));

        $response->assertStatus(403);
    }
}

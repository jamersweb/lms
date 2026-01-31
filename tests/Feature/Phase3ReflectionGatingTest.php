<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3ReflectionGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function createEnrolledUserAndModule(): array
    {
        $user = User::factory()->create([
            'level' => 'beginner',
            'has_bayah' => false,
            'gender' => 'male',
        ]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return [$user, $course, $module];
    }

    /**
     * Test: Next lesson blocked without reflection
     *
     * Scenario:
     * - Lesson 1 is completed
     * - Lesson 1 reflection is NOT submitted
     * - Lesson 2 should be blocked with reflection_required reason
     */
    public function test_next_lesson_blocked_without_reflection(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);
        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'duration_seconds' => 100,
        ]);

        // Mark lesson1 as completed
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        // Do NOT create reflection for lesson1

        // Attempt to access lesson2
        $response = $this->actingAs($user)
            ->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson2->id]));

        // Should be denied (redirected back with error message)
        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('reflection', strtolower($response->getSession()->get('error')));
    }

    /**
     * Test: Reflection unlocks next lesson
     *
     * Scenario:
     * - Lesson 1 is completed
     * - Lesson 1 reflection is submitted
     * - Lesson 2 should be accessible
     */
    public function test_reflection_unlocks_next_lesson(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);
        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'duration_seconds' => 100,
        ]);

        // Mark lesson1 as completed
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        // Submit reflection for lesson1
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => 'This lesson taught me valuable insights about patience and perseverance.',
            'review_status' => 'pending',
        ]);

        // Attempt to access lesson2
        $response = $this->actingAs($user)
            ->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson2->id]));

        // Should be allowed
        $response->assertStatus(200);
    }

    /**
     * Test: Cannot submit reflection before completion
     *
     * Scenario:
     * - Lesson 1 is NOT completed
     * - Attempt to submit reflection
     * - Should be denied with 422
     */
    public function test_cannot_submit_reflection_before_completion(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        // Do NOT mark lesson as completed

        // Attempt to submit reflection (with JSON header to get JSON response)
        $response = $this->actingAs($user)
            ->postJson(route('lessons.reflection', ['lesson' => $lesson1->id]), [
                'takeaway' => 'This lesson taught me valuable insights.',
            ]);

        // Should be denied
        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
            ])
            ->assertJsonFragment([
                'message' => 'Complete the lesson video first.',
            ]);
    }

    /**
     * Test: Validation - takeaway too short
     *
     * Scenario:
     * - Lesson is completed
     * - Attempt to submit reflection with < 30 characters
     * - Should be denied with validation error
     */
    public function test_reflection_validation_too_short(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        // Mark lesson as completed
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        // Attempt to submit reflection with too short text
        $response = $this->actingAs($user)
            ->post(route('lessons.reflection', ['lesson' => $lesson1->id]), [
                'takeaway' => 'Too short', // Less than 30 characters
            ]);

        // Should be denied with validation error
        $response->assertStatus(302) // Redirect with validation errors
            ->assertSessionHasErrors(['takeaway']);
    }

    /**
     * Test: Unique reflection per lesson per user (upsert behavior)
     *
     * Scenario:
     * - Submit reflection twice for same lesson
     * - Should update existing reflection, not create duplicate
     */
    public function test_unique_reflection_per_lesson_per_user(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        // Mark lesson as completed
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        $firstTakeaway = 'This lesson taught me valuable insights about patience and perseverance.';
        $secondTakeaway = 'Updated reflection: I learned even more after reviewing the lesson again.';

        // Submit reflection first time
        $response1 = $this->actingAs($user)
            ->post(route('lessons.reflection', ['lesson' => $lesson1->id]), [
                'takeaway' => $firstTakeaway,
            ]);

        $response1->assertStatus(302); // Redirect with success

        // Verify reflection exists
        $reflection1 = LessonReflection::where('user_id', $user->id)
            ->where('lesson_id', $lesson1->id)
            ->first();

        $this->assertNotNull($reflection1);
        $this->assertEquals($firstTakeaway, $reflection1->takeaway);
        $reflectionId = $reflection1->id;

        // Submit reflection second time (should update, not create new)
        $response2 = $this->actingAs($user)
            ->post(route('lessons.reflection', ['lesson' => $lesson1->id]), [
                'takeaway' => $secondTakeaway,
            ]);

        $response2->assertStatus(302); // Redirect with success

        // Verify only one reflection exists and it's updated
        $reflectionCount = LessonReflection::where('user_id', $user->id)
            ->where('lesson_id', $lesson1->id)
            ->count();

        $this->assertEquals(1, $reflectionCount);

        $reflection2 = LessonReflection::find($reflectionId);
        $this->assertEquals($secondTakeaway, $reflection2->takeaway);
        $this->assertEquals($reflectionId, $reflection2->id); // Same ID
    }

    /**
     * Test: Reflection submission requires valid completion
     *
     * Scenario:
     * - Lesson progress exists but completed_at is null
     * - Attempt to submit reflection
     * - Should be denied
     */
    public function test_reflection_requires_valid_completion(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        // Create progress but don't mark as completed
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => null, // Not completed
            'watched_seconds' => 50,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        // Attempt to submit reflection (with JSON header)
        $response = $this->actingAs($user)
            ->postJson(route('lessons.reflection', ['lesson' => $lesson1->id]), [
                'takeaway' => 'This lesson taught me valuable insights about patience and perseverance.',
            ]);

        // Should be denied
        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
            ])
            ->assertJsonFragment([
                'message' => 'Complete the lesson video first.',
            ]);
    }

    /**
     * Test: ProgressionService returns reflection_required reason
     *
     * Scenario:
     * - Lesson 1 completed, no reflection
     * - Check access to lesson 2 via ProgressionService
     * - Should return reflection_required reason
     */
    public function test_progression_service_returns_reflection_required(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);
        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'duration_seconds' => 100,
        ]);

        // Mark lesson1 as completed
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
        ]);

        // Do NOT create reflection

        // Check access via ProgressionService
        $progressionService = app(\App\Services\ProgressionService::class);
        $result = $progressionService->canAccessLesson($user, $lesson2);

        // Should be denied with reflection_required reason
        $this->assertFalse($result->allowed);
        $this->assertContains('reflection_required', $result->reasons);
    }
}

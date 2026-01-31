<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SequentialUnlockingTest extends TestCase
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

    public function test_lesson_2_blocked_before_lesson_1_completion(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        // Create two lessons with sequential order
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

        // Try to access lesson 2 before completing lesson 1
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $lesson2->id,
        ]));

        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('previous lesson', $response->getSession()->get('error'));
    }

    public function test_completing_lesson_1_unlocks_lesson_2(): void
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

        // Initially lesson 2 should be blocked
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $lesson2->id,
        ]));
        $response->assertRedirect();

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'watched_seconds' => 96, // sufficient
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
            'completed_at' => now(),
            'is_completed' => true,
        ]);

        // Now lesson 2 should be accessible
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $lesson2->id,
        ]));

        $response->assertOk();
    }

    public function test_one_at_a_time_strict_mode_lesson_3_locked_until_lesson_2_complete(): void
    {
        config(['progression.one_at_a_time' => true]);

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
        $lesson3 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 3,
            'duration_seconds' => 100,
        ]);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'watched_seconds' => 96,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'completed_at' => now(),
            'is_completed' => true,
        ]);

        // Lesson 2 should be accessible (next lesson)
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $lesson2->id,
        ]));
        $response->assertOk();

        // Lesson 3 should still be locked (not next)
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $lesson3->id,
        ]));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Complete lesson 2
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson2->id,
            'watched_seconds' => 96,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'completed_at' => now(),
            'is_completed' => true,
        ]);

        // Now lesson 3 should be accessible
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $lesson3->id,
        ]));
        $response->assertOk();
    }

    public function test_cross_module_independence(): void
    {
        [$user, $course, $moduleA] = $this->createEnrolledUserAndModule();
        $moduleB = Module::factory()->create(['course_id' => $course->id]);

        // Module A: lesson 1 and 2
        $moduleALesson1 = Lesson::factory()->create([
            'module_id' => $moduleA->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);
        $moduleALesson2 = Lesson::factory()->create([
            'module_id' => $moduleA->id,
            'sort_order' => 2,
            'duration_seconds' => 100,
        ]);

        // Module B: lesson 1 (independent)
        $moduleBLesson1 = Lesson::factory()->create([
            'module_id' => $moduleB->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        // Module A lesson 2 should be blocked (lesson 1 not completed)
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $moduleALesson2->id,
        ]));
        $response->assertRedirect();

        // Module B lesson 1 should be accessible (first lesson in its module)
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $moduleBLesson1->id,
        ]));
        $response->assertOk();
    }

    public function test_watch_session_start_blocked_for_sequential_lock(): void
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

        // Try to start watch session for lesson 2 before completing lesson 1
        $response = $this->actingAs($user)->postJson(route('lessons.watch.start', $lesson2));

        $response->assertStatus(403);
        $this->assertStringContainsString('previous lesson', $response->json('message') ?? '');
    }

    public function test_first_lesson_in_module_always_accessible(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        // First lesson should be accessible even without any progress
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $lesson1->id,
        ]));

        $response->assertOk();
    }

    public function test_course_show_includes_next_lesson(): void
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

        $response = $this->actingAs($user)->get(route('courses.show', $course));

        $response->assertOk();
        $data = $response->json('props.course');
        $this->assertNotNull($data['next_lesson']);
        $this->assertEquals($lesson1->id, $data['next_lesson']['id']);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'watched_seconds' => 96,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'completed_at' => now(),
            'is_completed' => true,
        ]);

        $response = $this->actingAs($user)->get(route('courses.show', $course));
        $data = $response->json('props.course');
        $this->assertEquals($lesson2->id, $data['next_lesson']['id']);
    }

    public function test_sequential_disabled_allows_all_lessons(): void
    {
        config(['progression.sequential_lessons' => false]);

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

        // Lesson 2 should be accessible even without completing lesson 1
        $response = $this->actingAs($user)->get(route('lessons.show', [
            'course' => $course->id,
            'lesson' => $lesson2->id,
        ]));

        $response->assertOk();
    }
}

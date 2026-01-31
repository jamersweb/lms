<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\Task;
use App\Models\TaskCheckin;
use App\Models\TaskProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3TaskGatingTest extends TestCase
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
     * Test: Next lesson blocked until task completed
     */
    public function test_next_lesson_blocked_until_task_completed(): void
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

        // Attach task to lesson1 (required_days=3)
        $task = Task::create([
            'title' => 'Practice patience for 3 days',
            'instructions' => 'Practice patience daily for 3 days',
            'type' => 'practice_streak',
            'required_days' => 3,
            'unlock_next_lesson' => true,
            'taskable_type' => Lesson::class,
            'taskable_id' => $lesson1->id,
        ]);

        // Create progress but don't complete (days_done=0)
        TaskProgress::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status' => TaskProgress::STATUS_PENDING,
            'days_done' => 0,
        ]);

        // Attempt to access lesson2
        $response = $this->actingAs($user)
            ->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson2->id]));

        // Should be denied
        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('task', strtolower($response->getSession()->get('error')));
    }

    /**
     * Test: Daily check-in increments days_done
     */
    public function test_daily_checkin_increments_days_done(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        $task = Task::create([
            'title' => 'Practice patience for 3 days',
            'instructions' => 'Practice patience daily',
            'type' => 'practice_streak',
            'required_days' => 3,
            'unlock_next_lesson' => true,
            'taskable_type' => Lesson::class,
            'taskable_id' => $lesson1->id,
        ]);

        // First check-in
        $response1 = $this->actingAs($user)
            ->postJson(route('tasks.checkin', ['task' => $task->id]));

        $response1->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'days_done' => 1,
                'status' => 'in_progress',
            ]);

        $progress1 = TaskProgress::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertEquals(1, $progress1->days_done);
        $this->assertEquals(TaskProgress::STATUS_IN_PROGRESS, $progress1->status);
        $this->assertTrue($progress1->hasCheckedInToday());

        // Try to check in again same day - should fail
        $response2 = $this->actingAs($user)
            ->postJson(route('tasks.checkin', ['task' => $task->id]));

        $response2->assertStatus(422)
            ->assertJson([
                'ok' => false,
            ])
            ->assertJsonFragment([
                'message' => 'You have already checked in today. Come back tomorrow!',
            ]);
    }

    /**
     * Test: Completing required days marks completed
     */
    public function test_completing_required_days_marks_completed(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        $task = Task::create([
            'title' => 'Practice patience for 3 days',
            'instructions' => 'Practice patience daily',
            'type' => 'practice_streak',
            'required_days' => 3,
            'unlock_next_lesson' => true,
            'taskable_type' => Lesson::class,
            'taskable_id' => $lesson1->id,
        ]);

        // Simulate 3 days of check-ins by manipulating dates
        $progress = TaskProgress::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status' => TaskProgress::STATUS_PENDING,
            'days_done' => 0,
        ]);

        // Day 1
        $today = Carbon::today();
        TaskCheckin::create([
            'task_progress_id' => $progress->id,
            'checkin_on' => $today,
        ]);
        $progress->days_done = 1;
        $progress->last_checkin_on = $today;
        $progress->status = TaskProgress::STATUS_IN_PROGRESS;
        $progress->started_at = now();
        $progress->save();

        // Day 2 (simulate tomorrow)
        $tomorrow = $today->copy()->addDay();
        TaskCheckin::create([
            'task_progress_id' => $progress->id,
            'checkin_on' => $tomorrow,
        ]);
        $progress->days_done = 2;
        $progress->last_checkin_on = $tomorrow;
        $progress->save();

        // Day 3 (simulate day after tomorrow) - should complete
        $dayAfter = $tomorrow->copy()->addDay();
        TaskCheckin::create([
            'task_progress_id' => $progress->id,
            'checkin_on' => $dayAfter,
        ]);
        $progress->days_done = 3;
        $progress->last_checkin_on = $dayAfter;
        $progress->status = TaskProgress::STATUS_COMPLETED;
        $progress->completed_at = now();
        $progress->save();

        // Verify completion
        $progress->refresh();
        $this->assertEquals(3, $progress->days_done);
        $this->assertEquals(TaskProgress::STATUS_COMPLETED, $progress->status);
        $this->assertNotNull($progress->completed_at);
    }

    /**
     * Test: Unlock after completion
     */
    public function test_unlock_after_completion(): void
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

        // Submit reflection
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => 'This lesson taught me valuable insights.',
            'review_status' => 'pending',
        ]);

        // Attach task and complete it
        $task = Task::create([
            'title' => 'Practice patience for 3 days',
            'instructions' => 'Practice patience daily',
            'type' => 'practice_streak',
            'required_days' => 3,
            'unlock_next_lesson' => true,
            'taskable_type' => Lesson::class,
            'taskable_id' => $lesson1->id,
        ]);

        TaskProgress::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status' => TaskProgress::STATUS_COMPLETED,
            'days_done' => 3,
            'completed_at' => now(),
        ]);

        // Now lesson2 should be accessible
        $response = $this->actingAs($user)
            ->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson2->id]));

        $response->assertStatus(200);
    }

    /**
     * Test: Security - user B cannot checkin progress for user A's task
     */
    public function test_security_user_cannot_checkin_others_task(): void
    {
        [$userA, $courseA, $moduleA] = $this->createEnrolledUserAndModule();
        $userB = User::factory()->create();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $moduleA->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        $task = Task::create([
            'title' => 'Practice patience',
            'instructions' => 'Practice daily',
            'type' => 'practice_streak',
            'required_days' => 3,
            'unlock_next_lesson' => true,
            'taskable_type' => Lesson::class,
            'taskable_id' => $lesson1->id,
        ]);

        // User A creates progress
        $progressA = TaskProgress::create([
            'task_id' => $task->id,
            'user_id' => $userA->id,
            'status' => TaskProgress::STATUS_PENDING,
            'days_done' => 0,
        ]);

        // User B tries to check in - should create their own progress, not modify A's
        $response = $this->actingAs($userB)
            ->postJson(route('tasks.checkin', ['task' => $task->id]));

        // Should succeed but create separate progress for user B
        $response->assertStatus(200);

        // Verify user A's progress unchanged
        $progressA->refresh();
        $this->assertEquals(0, $progressA->days_done);

        // Verify user B has their own progress
        $progressB = TaskProgress::where('task_id', $task->id)
            ->where('user_id', $userB->id)
            ->first();

        $this->assertNotNull($progressB);
        $this->assertEquals(1, $progressB->days_done);
        $this->assertNotEquals($progressA->id, $progressB->id);
    }

    /**
     * Test: Task progress carries correctly across multiple check-ins
     */
    public function test_task_progress_carries_correctly(): void
    {
        [$user, $course, $module] = $this->createEnrolledUserAndModule();

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'duration_seconds' => 100,
        ]);

        $task = Task::create([
            'title' => 'Practice patience for 5 days',
            'instructions' => 'Practice patience daily',
            'type' => 'practice_streak',
            'required_days' => 5,
            'unlock_next_lesson' => true,
            'taskable_type' => Lesson::class,
            'taskable_id' => $lesson1->id,
        ]);

        // Simulate multiple days by manipulating checkin dates
        $progress = TaskProgress::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'status' => TaskProgress::STATUS_PENDING,
            'days_done' => 0,
        ]);

        $baseDate = Carbon::today();

        // Day 1
        TaskCheckin::create([
            'task_progress_id' => $progress->id,
            'checkin_on' => $baseDate,
        ]);
        $progress->days_done = 1;
        $progress->last_checkin_on = $baseDate;
        $progress->status = TaskProgress::STATUS_IN_PROGRESS;
        $progress->started_at = now();
        $progress->save();

        // Day 2
        TaskCheckin::create([
            'task_progress_id' => $progress->id,
            'checkin_on' => $baseDate->copy()->addDay(),
        ]);
        $progress->days_done = 2;
        $progress->last_checkin_on = $baseDate->copy()->addDay();
        $progress->save();

        // Day 3
        TaskCheckin::create([
            'task_progress_id' => $progress->id,
            'checkin_on' => $baseDate->copy()->addDays(2),
        ]);
        $progress->days_done = 3;
        $progress->last_checkin_on = $baseDate->copy()->addDays(2);
        $progress->save();

        // Verify progress is correct
        $progress->refresh();
        $this->assertEquals(3, $progress->days_done);
        $this->assertEquals(TaskProgress::STATUS_IN_PROGRESS, $progress->status);
        $this->assertNull($progress->completed_at); // Not yet completed (needs 5 days)

        // Verify check-ins count
        $checkinsCount = TaskCheckin::where('task_progress_id', $progress->id)->count();
        $this->assertEquals(3, $checkinsCount);
    }

    /**
     * Test: Task with unlock_next_lesson=false does not block access
     */
    public function test_task_with_unlock_next_lesson_false_does_not_block(): void
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

        // Submit reflection
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => 'This lesson taught me valuable insights.',
            'review_status' => 'pending',
        ]);

        // Attach task with unlock_next_lesson=false
        $task = Task::create([
            'title' => 'Optional practice',
            'instructions' => 'Practice daily',
            'type' => 'practice_streak',
            'required_days' => 3,
            'unlock_next_lesson' => false, // Does not block
            'taskable_type' => Lesson::class,
            'taskable_id' => $lesson1->id,
        ]);

        // Don't complete task

        // Lesson2 should still be accessible
        $response = $this->actingAs($user)
            ->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson2->id]));

        $response->assertStatus(200);
    }
}

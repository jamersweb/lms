<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\User;
use App\Services\ProgressionService;
use App\Services\ReleaseScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class Phase3DripReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function absolute_release_blocks_access_before_time()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        // Create first lesson (completed) to avoid sequential lock
        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_at' => now()->addDay(), // Releases tomorrow
        ]);

        // Enroll user
        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now(),
        ]);

        // Complete lesson 1 to satisfy sequential unlock
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        // Add reflection for lesson 1 to satisfy reflection gate
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30), // Min 30 chars
            'review_status' => 'pending',
        ]);

        // Check lesson 2 - should be blocked by release schedule
        $progressionService = app(ProgressionService::class);
        $result = $progressionService->canAccessLesson($user, $lesson2);

        $this->assertFalse($result->allowed);
        $this->assertContains('not_released_yet', $result->reasons);
    }

    /** @test */
    public function absolute_release_allows_access_after_time()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        // Create first lesson (completed) to avoid sequential lock
        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_at' => now()->subMinute(), // Released 1 minute ago
        ]);

        // Enroll user
        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now(),
        ]);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        // Add reflection for lesson 1
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30),
            'review_status' => 'pending',
        ]);

        $progressionService = app(ProgressionService::class);
        $result = $progressionService->canAccessLesson($user, $lesson2);

        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function relative_release_blocks_until_enrollment_offset()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        // Create first lesson (completed) to avoid sequential lock
        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
        ]);

        // Create enrollment with started_at = now()
        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now(),
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_day_offset' => 1, // Available tomorrow
        ]);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        // Add reflection for lesson 1
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30),
            'review_status' => 'pending',
        ]);

        // Check immediately - should be blocked
        $progressionService = app(ProgressionService::class);
        $result = $progressionService->canAccessLesson($user, $lesson2);

        $this->assertFalse($result->allowed);
        $this->assertContains('not_released_yet', $result->reasons);

        // Simulate time travel: set started_at to yesterday
        $enrollment->update(['started_at' => now()->subDay()]);
        $enrollment->refresh();

        // Now should be allowed (started_at + 1 day = now)
        $result = $progressionService->canAccessLesson($user, $lesson2);
        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function release_at_overrides_offset()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        // Create first lesson (completed) to avoid sequential lock
        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now(),
        ]);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        // Add reflection for lesson 1
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30),
            'review_status' => 'pending',
        ]);

        // Lesson has both release_at (past) and offset (future)
        // release_at should override
        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_at' => now()->subMinute(), // Released 1 minute ago
            'release_day_offset' => 10, // Would be 10 days from now
        ]);

        $progressionService = app(ProgressionService::class);
        $result = $progressionService->canAccessLesson($user, $lesson2);

        // Should be allowed because release_at (absolute) overrides offset
        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function course_show_includes_release_props()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now(),
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'release_day_offset' => 1,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('courses.show', $course));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->has('course.modules.0.lessons.0.is_released')
                ->where('course.modules.0.lessons.0.is_released', false)
                ->has('course.modules.0.lessons.0.release_at')
                ->has('course.modules.0.lessons.0.release_human')
        );
    }

    /** @test */
    public function lesson_watch_route_blocks_if_not_released()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        // Create first lesson (completed) to avoid sequential lock
        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now(),
        ]);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        // Add reflection for lesson 1
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30),
            'review_status' => 'pending',
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_at' => now()->addDay(),
        ]);

        $this->actingAs($user);

        $response = $this->get(route('lessons.show', ['course' => $course->id, 'lesson' => $lesson2->id]));

        // Should redirect back with error
        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function release_schedule_service_calculates_relative_release_correctly()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $startedAt = now()->subDays(2);
        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => $startedAt,
            'started_at' => $startedAt,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'release_day_offset' => 3, // 3 days after started_at
        ]);

        $service = app(ReleaseScheduleService::class);
        $releaseAt = $service->getLessonReleaseAt($user, $lesson);

        $this->assertNotNull($releaseAt);
        $this->assertEquals($startedAt->copy()->addDays(3)->format('Y-m-d H:i'), $releaseAt->format('Y-m-d H:i'));
    }

    /** @test */
    public function release_schedule_service_returns_null_when_no_schedule()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            // No release_at or release_day_offset
        ]);

        $service = app(ReleaseScheduleService::class);
        $releaseAt = $service->getLessonReleaseAt($user, $lesson);

        $this->assertNull($releaseAt);
        $this->assertTrue($service->isReleased($user, $lesson)); // Should be released if no schedule
    }

    /** @test */
    public function release_schedule_works_with_sequential_unlock()
    {
        // Test that release schedule works alongside sequential unlock
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'started_at' => now()->subDays(2), // Enrolled 2 days ago
        ]);

        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'release_day_offset' => 0, // Available immediately
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
            'release_day_offset' => 1, // Available 1 day after enrollment
        ]);

        // Complete lesson 1
        LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 100,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
        ]);

        // Add reflection for lesson 1
        LessonReflection::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'takeaway' => str_repeat('a', 30),
            'review_status' => 'pending',
        ]);

        $progressionService = app(ProgressionService::class);

        // Lesson 2 should be accessible (started_at + 1 day = yesterday, which is < now)
        $result = $progressionService->canAccessLesson($user, $lesson2);
        $this->assertTrue($result->allowed);

        // But if we set started_at to today, lesson2 should be blocked
        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
        $enrollment->update(['started_at' => now()]);
        $enrollment->refresh();

        $result = $progressionService->canAccessLesson($user, $lesson2);
        $this->assertFalse($result->allowed);
        $this->assertContains('not_released_yet', $result->reasons);
    }
}

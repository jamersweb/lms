<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\User;
use App\Services\JourneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReflectionGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setupModuleWithTwoLessons(bool $requiresApproval = false): array
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 1,
            'requires_reflection' => true,
            'reflection_requires_approval' => $requiresApproval,
        ]);
        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'sort_order' => 2,
        ]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        return [$user, $course, $module, $lesson1, $lesson2];
    }

    public function test_next_lesson_locked_until_reflection_submitted(): void
    {
        [$user, $course, $module, $lesson1, $lesson2] = $this->setupModuleWithTwoLessons(false);

        // mark lesson1 completed and verified
        \App\Models\LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'is_completed' => true,
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        JourneyService::computeStatusesForCourse($user, $course);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson2->id,
            'status' => 'locked',
        ]);

        // Submit reflection (no approval required)
        LessonReflection::create([
            'lesson_id' => $lesson1->id,
            'user_id' => $user->id,
            'content' => 'My reflection',
            'submitted_at' => now(),
            'review_status' => 'pending',
        ]);

        JourneyService::computeStatusesForCourse($user, $course);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson2->id,
            'status' => 'available',
        ]);
    }

    public function test_next_lesson_locked_until_reflection_approved_when_required(): void
    {
        [$user, $course, $module, $lesson1, $lesson2] = $this->setupModuleWithTwoLessons(true);

        \App\Models\LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'is_completed' => true,
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        // Submit reflection but keep pending
        LessonReflection::create([
            'lesson_id' => $lesson1->id,
            'user_id' => $user->id,
            'content' => 'My reflection',
            'submitted_at' => now(),
            'review_status' => 'pending',
        ]);

        JourneyService::computeStatusesForCourse($user, $course);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson2->id,
            'status' => 'locked',
        ]);

        // Approve reflection
        LessonReflection::where('lesson_id', $lesson1->id)
            ->where('user_id', $user->id)
            ->update(['review_status' => 'approved']);

        JourneyService::computeStatusesForCourse($user, $course);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson2->id,
            'status' => 'available',
        ]);
    }
}


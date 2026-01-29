<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonReflection;
use App\Models\Module;
use App\Models\User;
use App\Http\Controllers\Admin\AnalyticsController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskMasteryAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_mastery_shows_struggling_lessons(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        
        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'requires_reflection' => true,
            'reflection_requires_approval' => true,
        ]);

        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'requires_reflection' => true,
            'reflection_requires_approval' => true,
        ]);

        // Lesson 1: High struggle rate (many need clarification)
        $users = User::factory()->count(10)->create();
        foreach ($users as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson1->id,
                'user_id' => $user->id,
                'content' => 'Test reflection',
                'review_status' => 'needs_clarification',
            ]);
        }

        // Lesson 2: Low struggle rate (mostly approved)
        $users2 = User::factory()->count(10)->create();
        foreach ($users2 as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson2->id,
                'user_id' => $user->id,
                'content' => 'Test reflection',
                'review_status' => 'approved',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Index')
            ->has('taskMastery', 2)
            ->where('taskMastery.0.lesson_id', $lesson1->id) // Highest struggle first
            ->where('taskMastery.0.struggle_rate', 100) // 100% need clarification
        );
    }

    public function test_task_mastery_calculates_approval_rate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'requires_reflection' => true,
        ]);

        // 5 approved, 3 pending, 2 need clarification
        $users = User::factory()->count(10)->create();
        foreach (array_slice($users->all(), 0, 5) as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson->id,
                'user_id' => $user->id,
                'content' => 'Test',
                'review_status' => 'approved',
            ]);
        }
        foreach (array_slice($users->all(), 5, 3) as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson->id,
                'user_id' => $user->id,
                'content' => 'Test',
                'review_status' => 'pending',
            ]);
        }
        foreach (array_slice($users->all(), 8, 2) as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson->id,
                'user_id' => $user->id,
                'content' => 'Test',
                'review_status' => 'needs_clarification',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('taskMastery', 1)
            ->where('taskMastery.0.approved', 5)
            ->where('taskMastery.0.pending', 3)
            ->where('taskMastery.0.needs_clarification', 2)
            ->where('taskMastery.0.approval_rate', 50) // 5/10 = 50%
            ->where('taskMastery.0.struggle_rate', 50) // (3+2)/10 = 50%
        );
    }

    public function test_task_mastery_only_shows_lessons_with_reflections(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        
        $lessonWithReflection = Lesson::factory()->create([
            'module_id' => $module->id,
            'requires_reflection' => true,
        ]);

        $lessonWithoutReflection = Lesson::factory()->create([
            'module_id' => $module->id,
            'requires_reflection' => false,
        ]);

        $user = User::factory()->create();
        LessonReflection::create([
            'lesson_id' => $lessonWithReflection->id,
            'user_id' => $user->id,
            'content' => 'Test',
            'review_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('taskMastery', 1)
            ->where('taskMastery.0.lesson_id', $lessonWithReflection->id)
        );
    }

    public function test_task_mastery_sorted_by_struggle_rate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        
        // Lesson 1: 80% struggle
        $lesson1 = Lesson::factory()->create([
            'module_id' => $module->id,
            'requires_reflection' => true,
        ]);
        $users1 = User::factory()->count(10)->create();
        foreach (array_slice($users1->all(), 0, 8) as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson1->id,
                'user_id' => $user->id,
                'content' => 'Test',
                'review_status' => 'needs_clarification',
            ]);
        }
        foreach (array_slice($users1->all(), 8, 2) as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson1->id,
                'user_id' => $user->id,
                'content' => 'Test',
                'review_status' => 'approved',
            ]);
        }

        // Lesson 2: 20% struggle
        $lesson2 = Lesson::factory()->create([
            'module_id' => $module->id,
            'requires_reflection' => true,
        ]);
        $users2 = User::factory()->count(10)->create();
        foreach (array_slice($users2->all(), 0, 8) as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson2->id,
                'user_id' => $user->id,
                'content' => 'Test',
                'review_status' => 'approved',
            ]);
        }
        foreach (array_slice($users2->all(), 8, 2) as $user) {
            LessonReflection::create([
                'lesson_id' => $lesson2->id,
                'user_id' => $user->id,
                'content' => 'Test',
                'review_status' => 'needs_clarification',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('taskMastery', 2)
            ->where('taskMastery.0.lesson_id', $lesson1->id) // Highest struggle first
            ->where('taskMastery.0.struggle_rate', 80)
            ->where('taskMastery.1.lesson_id', $lesson2->id) // Lower struggle second
            ->where('taskMastery.1.struggle_rate', 20)
        );
    }
}

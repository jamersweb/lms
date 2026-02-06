<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonVideoProgress;
use App\Models\Module;
use App\Models\Note;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_service_returns_correct_data_structure()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['module_id' => $module->id]);
        $lesson2 = Lesson::factory()->create(['module_id' => $module->id]);
        $lesson3 = Lesson::factory()->create(['module_id' => $module->id]);

        // Enroll user
        $user->enrollments()->create(['course_id' => $course->id]);

        // Create some progress
        LessonProgress::factory()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => now(),
            'watched_seconds' => 600,
        ]);

        // Create video progress (in-progress)
        LessonVideoProgress::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson2->id,
            'provider' => 'youtube',
            'duration_seconds' => 1200,
            'last_position_seconds' => 300,
            'percent_complete' => 25,
            'is_completed' => false,
        ]);

        // Create a note
        Note::factory()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson1->id,
            'title' => 'Test Note',
            'content' => 'Test content',
        ]);

        $service = new DashboardService();
        $data = $service->getDashboardData($user);

        // Verify structure
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('continue_watching', $data);
        $this->assertArrayHasKey('watch_time', $data);
        $this->assertArrayHasKey('streak', $data);
        $this->assertArrayHasKey('recent_notes', $data);
        $this->assertArrayHasKey('continue_learning', $data);

        // Verify stats
        $this->assertEquals(1, $data['stats']['watched_lessons']);
        $this->assertEquals(2, $data['stats']['remaining_lessons']);
        $this->assertEquals(3, $data['stats']['total_lessons']);
        $this->assertGreaterThanOrEqual(0, $data['stats']['total_watch_time_seconds']);
        $this->assertIsString($data['stats']['total_watch_time_formatted']);

        // Verify continue watching
        $this->assertIsArray($data['continue_watching']);
        $this->assertCount(1, $data['continue_watching']);
        $this->assertEquals($lesson2->id, $data['continue_watching'][0]['lesson_id']);
        $this->assertEquals(25, $data['continue_watching'][0]['percent_complete']);

        // Verify watch time
        $this->assertArrayHasKey('today_seconds', $data['watch_time']);
        $this->assertArrayHasKey('today_formatted', $data['watch_time']);
        $this->assertArrayHasKey('daily_goal_seconds', $data['watch_time']);
        $this->assertArrayHasKey('daily_goal_progress', $data['watch_time']);

        // Verify streak
        $this->assertArrayHasKey('days', $data['streak']);
        $this->assertIsInt($data['streak']['days']);

        // Verify recent notes
        $this->assertIsArray($data['recent_notes']);
        $this->assertGreaterThanOrEqual(0, count($data['recent_notes']));
    }

    public function test_dashboard_controller_returns_correct_response()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => 
            $page->has('stats')
                ->has('continue_watching')
                ->has('watch_time')
                ->has('streak')
                ->has('recent_notes')
        );
    }

    public function test_continue_watching_only_shows_in_progress_lessons()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['module_id' => $module->id]);
        $lesson2 = Lesson::factory()->create(['module_id' => $module->id]);
        $lesson3 = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        // Completed lesson (should not appear)
        LessonVideoProgress::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson1->id,
            'percent_complete' => 100,
            'is_completed' => true,
        ]);

        // In-progress lesson (should appear)
        LessonVideoProgress::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson2->id,
            'percent_complete' => 50,
            'is_completed' => false,
        ]);

        // Not started (should not appear)
        // No video progress record

        $service = new DashboardService();
        $data = $service->getDashboardData($user);

        $this->assertCount(1, $data['continue_watching']);
        $this->assertEquals($lesson2->id, $data['continue_watching'][0]['lesson_id']);
    }

    public function test_daily_goal_progress_calculation()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $user->enrollments()->create(['course_id' => $course->id]);

        // Watch 15 minutes today (900 seconds) - using LessonProgress
        LessonProgress::factory()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'watched_seconds' => 900,
            'updated_at' => now(),
        ]);

        $service = new DashboardService();
        $data = $service->getDashboardData($user);

        // Daily goal is 1800 seconds (30 minutes)
        // 900 / 1800 = 50%
        $this->assertEquals(50, round($data['watch_time']['daily_goal_progress']));
        $this->assertEquals(15, round($data['watch_time']['today_minutes']));
    }
}

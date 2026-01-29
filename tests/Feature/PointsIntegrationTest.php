<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Habit;
use App\Models\Lesson;
use App\Services\PointsService;
use Carbon\Carbon;

class PointsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_habit_completion_awards_points()
    {
        $user = User::factory()->create();
        $habit = Habit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('habits.log', $habit), [
            'date' => Carbon::today()->toDateString(),
            'status' => 'done',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('points_events', [
            'user_id' => $user->id,
            'event_type' => 'habit_done',
            'points' => 2,
        ]);
    }

    public function test_lesson_completion_awards_points()
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create();
        $module = \App\Models\Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'video_duration_seconds' => 10]);

        \App\Models\Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        \App\Models\LessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'time_watched_seconds' => 10,
            'max_playback_rate_seen' => 1.0,
            'seek_detected' => false,
        ]);

        $this->actingAs($user)->post(route('lessons.complete', $lesson));

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'is_completed' => true,
        ]);

        $this->assertDatabaseHas('points_events', [
            'user_id' => $user->id,
            'event_type' => 'lesson_completed',
            'points' => 10,
        ]);
    }
}

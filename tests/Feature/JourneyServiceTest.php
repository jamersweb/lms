<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enroll_initializes_first_lesson_available(): void
    {
        $user = User::factory()->create([
            'gender' => 'male',
            'has_bayah' => true,
            'level' => 'beginner',
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lessons = collect([
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 1]),
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 2]),
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 3]),
        ]);

        $this->actingAs($user)->post("/courses/{$course->id}/enroll");

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[0]->id,
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[1]->id,
            'status' => 'locked',
        ]);
    }

    public function test_completing_first_unlocks_second(): void
    {
        $user = User::factory()->create([
            'gender' => 'male',
            'has_bayah' => true,
            'level' => 'beginner',
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lessons = collect([
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 1]),
            Lesson::factory()->create(['module_id' => $module->id, 'sort_order' => 2]),
        ]);

        $this->actingAs($user)->post("/courses/{$course->id}/enroll");

        // Complete first lesson
        $this->actingAs($user)->post("/lessons/{$lessons[0]->id}/complete");

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[0]->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lessons[1]->id,
            'status' => 'available',
        ]);
    }
}


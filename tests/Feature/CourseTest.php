<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_courses_list(): void
    {
        $user = User::factory()->create([
            'gender' => 'male',
            'has_bayah' => true,
            'level' => 'expert',
        ]);
        Course::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/courses');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Courses/Index')
            ->has('courses', 3)
        );
    }

    public function test_authenticated_users_can_view_course_details(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->count(2)->create(['module_id' => $module->id]);

        $response = $this->actingAs($user)->get("/courses/{$course->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Courses/Show')
            ->has('course')
        );
    }

    public function test_users_can_enroll_in_courses(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($user)->post("/courses/{$course->id}/enroll");

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $response->assertRedirect("/courses/{$course->id}");
    }

    public function test_users_cannot_enroll_twice_in_same_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        
        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()
        ]);

        $response = $this->actingAs($user)->post("/courses/{$course->id}/enroll");

        $this->assertEquals(1, Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->count());
    }

    public function test_course_progress_is_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(4)->create(['module_id' => $module->id]);

        Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now()
        ]);

        // Complete 2 out of 4 lessons
        foreach ($lessons->take(2) as $lesson) {
            $user->lessonProgress()->create([
                'lesson_id' => $lesson->id,
                'completed_at' => now(),
                'progress_percentage' => 100
            ]);
        }

        $response = $this->actingAs($user)->get('/courses');

        $response->assertInertia(fn ($page) => $page
            ->where('courses.0.progress', 50) // 2/4 = 50%
        );
    }
}

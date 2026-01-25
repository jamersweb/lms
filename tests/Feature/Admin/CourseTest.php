<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_courses_index()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.courses.index'));

        $response->assertStatus(200);
    }

    public function test_student_cannot_view_courses_index()
    {
        $student = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($student)->get(route('admin.courses.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_course()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'title' => 'New Course',
            'slug' => 'new-course',
            'description' => 'Test description',
            'sort_order' => 1,
        ]);

        $response->assertRedirect(route('admin.courses.index'));
        $this->assertDatabaseHas('courses', ['title' => 'New Course']);
    }
}

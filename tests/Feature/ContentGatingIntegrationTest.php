<?php

namespace Tests\Feature;

use App\Models\ContentRule;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentGatingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function locked_lesson_direct_access_denied(): void
    {
        $user = User::factory()->create([
            'level' => 'beginner',
            'has_bayah' => false,
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        // Set lesson rule requiring expert level
        ContentRule::factory()->for($lesson, 'ruleable')->withMinLevel('expert')->create();

        // Enroll user in course
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/courses/{$course->id}/lessons/{$lesson->id}");

        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('locked', session('error'));
    }

    /** @test */
    public function course_index_returns_lock_metadata(): void
    {
        $user = User::factory()->create([
            'has_bayah' => false,
        ]);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->requiresBayah()->create();

        $response = $this->actingAs($user)
            ->get('/courses');

        $response->assertOk();

        $courses = $response->viewData('page')['props']['courses'];
        $this->assertNotEmpty($courses);

        $foundCourse = collect($courses)->firstWhere('id', $course->id);
        $this->assertNotNull($foundCourse);
        $this->assertTrue($foundCourse['is_locked']);
        $this->assertContains('requires_bayah', $foundCourse['lock_reasons']);
        $this->assertNotEmpty($foundCourse['lock_message']);
        $this->assertTrue($foundCourse['requires_bayah']);
    }

    /** @test */
    public function course_show_marks_lesson_locked(): void
    {
        $user = User::factory()->create([
            'gender' => 'male',
            'level' => 'beginner',
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        // Set lesson rule requiring expert level
        ContentRule::factory()->for($lesson, 'ruleable')->withMinLevel('expert')->create();

        $response = $this->actingAs($user)
            ->get("/courses/{$course->id}");

        $response->assertOk();

        $courseData = $response->viewData('page')['props']['course'];
        $this->assertNotEmpty($courseData['modules']);

        $firstModule = $courseData['modules'][0];
        $this->assertNotEmpty($firstModule['lessons']);

        $foundLesson = collect($firstModule['lessons'])->firstWhere('id', $lesson->id);
        $this->assertNotNull($foundLesson);
        $this->assertTrue($foundLesson['is_locked']);
        $this->assertContains('level_too_low', $foundLesson['lock_reasons']);
        $this->assertNotEmpty($foundLesson['lock_message']);
        $this->assertSame('expert', $foundLesson['required_level']);
    }

    /** @test */
    public function config_hide_locked_courses_works(): void
    {
        config(['lms.show_locked_courses' => false]);

        $user = User::factory()->create([
            'has_bayah' => false,
        ]);

        $lockedCourse = Course::factory()->create();
        ContentRule::factory()->for($lockedCourse, 'ruleable')->requiresBayah()->create();

        $allowedCourse = Course::factory()->create();
        // No rule = allowed

        $response = $this->actingAs($user)
            ->get('/courses');

        $response->assertOk();

        $courses = $response->viewData('page')['props']['courses'];
        $courseIds = collect($courses)->pluck('id')->toArray();

        $this->assertNotContains($lockedCourse->id, $courseIds);
        $this->assertContains($allowedCourse->id, $courseIds);
    }

    /** @test */
    public function config_show_locked_courses_shows_all_with_lock_badges(): void
    {
        config(['lms.show_locked_courses' => true]);

        $user = User::factory()->create([
            'has_bayah' => false,
        ]);

        $lockedCourse = Course::factory()->create();
        ContentRule::factory()->for($lockedCourse, 'ruleable')->requiresBayah()->create();

        $allowedCourse = Course::factory()->create();

        $response = $this->actingAs($user)
            ->get('/courses');

        $response->assertOk();

        $courses = $response->viewData('page')['props']['courses'];
        $courseIds = collect($courses)->pluck('id')->toArray();

        // Both courses should be shown
        $this->assertContains($lockedCourse->id, $courseIds);
        $this->assertContains($allowedCourse->id, $courseIds);

        // Locked course should have lock metadata
        $foundLockedCourse = collect($courses)->firstWhere('id', $lockedCourse->id);
        $this->assertTrue($foundLockedCourse['is_locked']);

        // Allowed course should not be locked
        $foundAllowedCourse = collect($courses)->firstWhere('id', $allowedCourse->id);
        $this->assertFalse($foundAllowedCourse['is_locked']);
    }

    /** @test */
    public function course_show_displays_module_lock_status(): void
    {
        $user = User::factory()->create([
            'has_bayah' => false,
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        ContentRule::factory()->for($module, 'ruleable')->requiresBayah()->create();

        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $response = $this->actingAs($user)
            ->get("/courses/{$course->id}");

        $response->assertOk();

        $courseData = $response->viewData('page')['props']['course'];
        $foundModule = collect($courseData['modules'])->firstWhere('id', $module->id);

        $this->assertNotNull($foundModule);
        $this->assertTrue($foundModule['is_locked']);
        $this->assertContains('requires_bayah', $foundModule['lock_reasons']);
        $this->assertNotEmpty($foundModule['lock_message']);
    }

    /** @test */
    public function lesson_with_gender_restriction_shows_correct_lock_message(): void
    {
        $maleUser = User::factory()->create(['gender' => 'male']);
        $femaleUser = User::factory()->create(['gender' => 'female']);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        ContentRule::factory()->for($lesson, 'ruleable')->forGender('male')->create();

        // Male user should see lesson (not locked)
        $response = $this->actingAs($maleUser)
            ->get("/courses/{$course->id}");

        $courseData = $response->viewData('page')['props']['course'];
        $foundLesson = collect($courseData['modules'][0]['lessons'])->firstWhere('id', $lesson->id);
        $this->assertFalse($foundLesson['is_locked']);

        // Female user should see lesson as locked
        $response = $this->actingAs($femaleUser)
            ->get("/courses/{$course->id}");

        $courseData = $response->viewData('page')['props']['course'];
        $foundLesson = collect($courseData['modules'][0]['lessons'])->firstWhere('id', $lesson->id);
        $this->assertTrue($foundLesson['is_locked']);
        $this->assertStringContainsString('brothers only', $foundLesson['lock_message']);
    }

    /** @test */
    public function lesson_with_multiple_lock_reasons_shows_all_reasons(): void
    {
        $user = User::factory()->create([
            'level' => 'beginner',
            'has_bayah' => false,
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        ContentRule::factory()->for($lesson, 'ruleable')
            ->requiresBayah()
            ->withMinLevel('expert')
            ->create();

        $response = $this->actingAs($user)
            ->get("/courses/{$course->id}");

        $response->assertOk();

        $courseData = $response->viewData('page')['props']['course'];
        $foundLesson = collect($courseData['modules'][0]['lessons'])->firstWhere('id', $lesson->id);

        $this->assertTrue($foundLesson['is_locked']);
        $this->assertContains('requires_bayah', $foundLesson['lock_reasons']);
        $this->assertContains('level_too_low', $foundLesson['lock_reasons']);
        $this->assertStringContainsString('Bay\'ah', $foundLesson['lock_message']);
        $this->assertStringContainsString('Expert', $foundLesson['lock_message']);
    }

    /** @test */
    public function course_show_accessible_even_if_locked(): void
    {
        $user = User::factory()->create(['has_bayah' => false]);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->requiresBayah()->create();

        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $response = $this->actingAs($user)
            ->get("/courses/{$course->id}");

        $response->assertOk();

        $courseData = $response->viewData('page')['props']['course'];
        $this->assertTrue($courseData['is_locked']);
        $this->assertNotEmpty($courseData['lock_message']);

        // Lessons should be marked as locked
        $foundLesson = collect($courseData['modules'][0]['lessons'])->firstWhere('id', $lesson->id);
        $this->assertTrue($foundLesson['is_locked']);
    }

    /** @test */
    public function lesson_watch_route_blocks_before_returning_lesson_data(): void
    {
        $user = User::factory()->create([
            'level' => 'beginner',
            'has_bayah' => false,
        ]);

        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        ContentRule::factory()->for($lesson, 'ruleable')
            ->requiresBayah()
            ->withMinLevel('expert')
            ->create();

        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/courses/{$course->id}/lessons/{$lesson->id}");

        // Should redirect, not return lesson data
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Ensure no lesson data was rendered
        $this->assertStringNotContainsString($lesson->title, $response->getContent());
    }
}

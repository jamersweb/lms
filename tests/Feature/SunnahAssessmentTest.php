<?php

namespace Tests\Feature;

use App\Models\AssessmentResponse;
use App\Models\Course;
use App\Models\CourseExemption;
use App\Models\Module;
use App\Models\SunnahAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SunnahAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_assessment_for_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $assessment = SunnahAssessment::create([
            'course_id' => $course->id,
            'title' => 'Sunnah Practices Assessment',
            'description' => 'Assess your current practices',
            'questions' => [
                [
                    'key' => 'morning_adhkar',
                    'text' => 'Do you perform morning Adhkar?',
                    'module_id' => $module->id,
                ],
                [
                    'key' => 'before_eating',
                    'text' => 'Do you say Bismillah before eating?',
                    'module_id' => $module->id,
                ],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('assessments.show', $course));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Assessments/Show')
            ->has('assessment.questions', 2)
        );
    }

    public function test_user_can_submit_assessment_responses(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $assessment = SunnahAssessment::create([
            'course_id' => $course->id,
            'title' => 'Test Assessment',
            'questions' => [
                [
                    'key' => 'test_question',
                    'text' => 'Test question?',
                    'module_id' => $module->id,
                ],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('assessments.store', $course), [
            'responses' => [
                [
                    'question_key' => 'test_question',
                    'already_practicing' => true,
                    'notes' => 'I already do this',
                ],
            ],
        ]);

        $response->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('assessment_responses', [
            'user_id' => $user->id,
            'sunnah_assessment_id' => $assessment->id,
            'question_key' => 'test_question',
            'already_practicing' => true,
        ]);
    }

    public function test_exemption_is_created_when_user_already_practices(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);

        $assessment = SunnahAssessment::create([
            'course_id' => $course->id,
            'title' => 'Test Assessment',
            'questions' => [
                [
                    'key' => 'test_question',
                    'text' => 'Test question?',
                    'module_id' => $module->id,
                ],
            ],
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('assessments.store', $course), [
            'responses' => [
                [
                    'question_key' => 'test_question',
                    'already_practicing' => true,
                    'notes' => 'I already do this',
                ],
            ],
        ]);

        $this->assertDatabaseHas('course_exemptions', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'sunnah_assessment_id' => $assessment->id,
        ]);

        $exemption = CourseExemption::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $this->assertContains($module->id, $exemption->exempted_modules);
    }

    public function test_user_cannot_take_assessment_twice(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $assessment = SunnahAssessment::create([
            'course_id' => $course->id,
            'title' => 'Test Assessment',
            'questions' => [
                ['key' => 'test', 'text' => 'Test?', 'module_id' => null],
            ],
            'is_active' => true,
        ]);

        CourseExemption::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'sunnah_assessment_id' => $assessment->id,
            'exempted_modules' => [],
        ]);

        $response = $this->actingAs($user)->get(route('assessments.show', $course));

        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('info');
    }

    public function test_assessment_responses_are_unique_per_user_and_question(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $assessment = SunnahAssessment::factory()->create(['course_id' => $course->id]);

        AssessmentResponse::create([
            'user_id' => $user->id,
            'sunnah_assessment_id' => $assessment->id,
            'question_key' => 'test',
            'already_practicing' => false,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AssessmentResponse::create([
            'user_id' => $user->id,
            'sunnah_assessment_id' => $assessment->id,
            'question_key' => 'test',
            'already_practicing' => true,
        ]);
    }
}

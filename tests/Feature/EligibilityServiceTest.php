<?php

namespace Tests\Feature;

use App\Models\ContentRule;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Services\EligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private EligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EligibilityService();
    }

    /** @test */
    public function no_rules_returns_allowed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $result = $this->service->canAccessCourse($user, $course);

        $this->assertTrue($result->allowed);
        $this->assertEmpty($result->reasons);
    }

    /** @test */
    public function course_gender_rule_male_only_allows_male_user(): void
    {
        $maleUser = User::factory()->create(['gender' => 'male']);
        $femaleUser = User::factory()->create(['gender' => 'female']);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->forGender('male')->create();

        $maleResult = $this->service->canAccessCourse($maleUser, $course);
        $femaleResult = $this->service->canAccessCourse($femaleUser, $course);

        $this->assertTrue($maleResult->allowed);
        $this->assertFalse($femaleResult->allowed);
        $this->assertContains('gender_mismatch', $femaleResult->reasons);
        $this->assertSame('male', $femaleResult->requiredGender);
    }

    /** @test */
    public function course_gender_rule_female_only_allows_female_user(): void
    {
        $maleUser = User::factory()->create(['gender' => 'male']);
        $femaleUser = User::factory()->create(['gender' => 'female']);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->forGender('female')->create();

        $maleResult = $this->service->canAccessCourse($maleUser, $course);
        $femaleResult = $this->service->canAccessCourse($femaleUser, $course);

        $this->assertFalse($maleResult->allowed);
        $this->assertTrue($femaleResult->allowed);
        $this->assertContains('gender_mismatch', $maleResult->reasons);
    }

    /** @test */
    public function bayah_required_at_module_level_denies_user_without_bayah(): void
    {
        $userWithoutBayah = User::factory()->create(['has_bayah' => false]);
        $userWithBayah = User::factory()->create(['has_bayah' => true]);
        $module = Module::factory()->create();
        ContentRule::factory()->for($module, 'ruleable')->requiresBayah()->create();

        $resultWithoutBayah = $this->service->canAccessModule($userWithoutBayah, $module);
        $resultWithBayah = $this->service->canAccessModule($userWithBayah, $module);

        $this->assertFalse($resultWithoutBayah->allowed);
        $this->assertContains('requires_bayah', $resultWithoutBayah->reasons);
        $this->assertTrue($resultWithoutBayah->requiresBayah);
        $this->assertTrue($resultWithBayah->allowed);
    }

    /** @test */
    public function lesson_min_level_expert_denies_beginner_user(): void
    {
        $beginnerUser = User::factory()->create(['level' => 'beginner']);
        $intermediateUser = User::factory()->create(['level' => 'intermediate']);
        $expertUser = User::factory()->create(['level' => 'expert']);
        $lesson = Lesson::factory()->create();
        ContentRule::factory()->for($lesson, 'ruleable')->withMinLevel('expert')->create();

        $beginnerResult = $this->service->canAccessLesson($beginnerUser, $lesson);
        $intermediateResult = $this->service->canAccessLesson($intermediateUser, $lesson);
        $expertResult = $this->service->canAccessLesson($expertUser, $lesson);

        $this->assertFalse($beginnerResult->allowed);
        $this->assertContains('level_too_low', $beginnerResult->reasons);
        $this->assertSame('expert', $beginnerResult->requiredLevel);

        $this->assertFalse($intermediateResult->allowed);
        $this->assertContains('level_too_low', $intermediateResult->reasons);

        $this->assertTrue($expertResult->allowed);
    }

    /** @test */
    public function inheritance_additive_course_requires_bayah_lesson_has_no_rule_bayah_still_required(): void
    {
        $userWithoutBayah = User::factory()->create(['has_bayah' => false]);
        $userWithBayah = User::factory()->create(['has_bayah' => true]);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->requiresBayah()->create();

        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        // Lesson has no rule

        $resultWithoutBayah = $this->service->canAccessLesson($userWithoutBayah, $lesson);
        $resultWithBayah = $this->service->canAccessLesson($userWithBayah, $lesson);

        $this->assertFalse($resultWithoutBayah->allowed);
        $this->assertContains('requires_bayah', $resultWithoutBayah->reasons);
        $this->assertTrue($resultWithBayah->allowed);
    }

    /** @test */
    public function conflicting_gender_rules_denies_access(): void
    {
        $user = User::factory()->create(['gender' => 'male']);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->forGender('male')->create();

        $module = Module::factory()->create(['course_id' => $course->id]);
        ContentRule::factory()->for($module, 'ruleable')->forGender('female')->create();

        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $result = $this->service->canAccessLesson($user, $lesson);

        $this->assertFalse($result->allowed);
        $this->assertContains('conflicting_gender_rules', $result->reasons);
    }

    /** @test */
    public function multiple_reasons_includes_both_requires_bayah_and_level_too_low(): void
    {
        $user = User::factory()->create([
            'has_bayah' => false,
            'level' => 'beginner',
        ]);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')
            ->requiresBayah()
            ->withMinLevel('expert')
            ->create();

        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $result = $this->service->canAccessLesson($user, $lesson);

        $this->assertFalse($result->allowed);
        $this->assertContains('requires_bayah', $result->reasons);
        $this->assertContains('level_too_low', $result->reasons);
        $this->assertCount(2, $result->reasons);
        $this->assertSame('expert', $result->requiredLevel);
        $this->assertTrue($result->requiresBayah);
    }

    /** @test */
    public function level_inheritance_takes_highest_min_level(): void
    {
        $intermediateUser = User::factory()->create(['level' => 'intermediate']);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->withMinLevel('beginner')->create();

        $module = Module::factory()->create(['course_id' => $course->id]);
        ContentRule::factory()->for($module, 'ruleable')->withMinLevel('intermediate')->create();

        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        ContentRule::factory()->for($lesson, 'ruleable')->withMinLevel('expert')->create();

        // Intermediate user should be denied because expert is required (highest)
        $result = $this->service->canAccessLesson($intermediateUser, $lesson);

        $this->assertFalse($result->allowed);
        $this->assertContains('level_too_low', $result->reasons);
        $this->assertSame('expert', $result->requiredLevel);
    }

    /** @test */
    public function user_without_gender_denied_when_gender_rule_exists(): void
    {
        $userWithoutGender = User::factory()->create(['gender' => null]);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->forGender('male')->create();

        $result = $this->service->canAccessCourse($userWithoutGender, $course);

        $this->assertFalse($result->allowed);
        $this->assertContains('gender_mismatch', $result->reasons);
    }

    /** @test */
    public function user_without_level_treated_as_beginner(): void
    {
        $userWithoutLevel = User::factory()->create(['level' => null]);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->withMinLevel('beginner')->create();

        $result = $this->service->canAccessCourse($userWithoutLevel, $course);

        // Should be allowed since null level defaults to beginner
        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function user_without_level_denied_when_intermediate_required(): void
    {
        $userWithoutLevel = User::factory()->create(['level' => null]);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->withMinLevel('intermediate')->create();

        $result = $this->service->canAccessCourse($userWithoutLevel, $course);

        $this->assertFalse($result->allowed);
        $this->assertContains('level_too_low', $result->reasons);
    }

    /** @test */
    public function can_access_module_evaluates_course_and_module_rules(): void
    {
        $user = User::factory()->create([
            'has_bayah' => true,
            'gender' => 'male',
            'level' => 'intermediate',
        ]);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->requiresBayah()->create();

        $module = Module::factory()->create(['course_id' => $course->id]);
        ContentRule::factory()->for($module, 'ruleable')->forGender('male')->create();

        $result = $this->service->canAccessModule($user, $module);

        $this->assertTrue($result->allowed);
    }

    /** @test */
    public function can_access_course_only_evaluates_course_rules(): void
    {
        $user = User::factory()->create(['has_bayah' => false]);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->requiresBayah()->create();

        $result = $this->service->canAccessCourse($user, $course);

        $this->assertFalse($result->allowed);
        $this->assertContains('requires_bayah', $result->reasons);
    }

    /** @test */
    public function conflicting_gender_rules_course_male_module_female_lesson_none_denies(): void
    {
        $user = User::factory()->create(['gender' => 'male']);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->forGender('male')->create();

        $module = Module::factory()->create(['course_id' => $course->id]);
        ContentRule::factory()->for($module, 'ruleable')->forGender('female')->create();

        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        // Lesson has no rule

        $result = $this->service->canAccessLesson($user, $lesson);

        $this->assertFalse($result->allowed);
        $this->assertContains('conflicting_gender_rules', $result->reasons);
    }

    /** @test */
    public function max_min_level_selection_course_intermediate_lesson_expert_requires_expert(): void
    {
        $intermediateUser = User::factory()->create(['level' => 'intermediate']);
        $expertUser = User::factory()->create(['level' => 'expert']);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->withMinLevel('intermediate')->create();

        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        ContentRule::factory()->for($lesson, 'ruleable')->withMinLevel('expert')->create();

        $intermediateResult = $this->service->canAccessLesson($intermediateUser, $lesson);
        $expertResult = $this->service->canAccessLesson($expertUser, $lesson);

        $this->assertFalse($intermediateResult->allowed);
        $this->assertContains('level_too_low', $intermediateResult->reasons);
        $this->assertSame('expert', $intermediateResult->requiredLevel);

        $this->assertTrue($expertResult->allowed);
    }

    /** @test */
    public function additive_bayah_module_true_overrides_course_false_still_requires(): void
    {
        $userWithoutBayah = User::factory()->create(['has_bayah' => false]);
        $userWithBayah = User::factory()->create(['has_bayah' => true]);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->create(['requires_bayah' => false]);

        $module = Module::factory()->create(['course_id' => $course->id]);
        ContentRule::factory()->for($module, 'ruleable')->requiresBayah()->create();

        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $resultWithoutBayah = $this->service->canAccessLesson($userWithoutBayah, $lesson);
        $resultWithBayah = $this->service->canAccessLesson($userWithBayah, $lesson);

        $this->assertFalse($resultWithoutBayah->allowed);
        $this->assertContains('requires_bayah', $resultWithoutBayah->reasons);
        $this->assertTrue($resultWithoutBayah->requiresBayah);
        $this->assertTrue($resultWithBayah->allowed);
    }

    /** @test */
    public function eligibility_result_contains_all_required_fields(): void
    {
        $user = User::factory()->create([
            'gender' => 'female',
            'level' => 'beginner',
            'has_bayah' => false,
        ]);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')
            ->requiresBayah()
            ->withMinLevel('expert')
            ->forGender('male')
            ->create();

        $result = $this->service->canAccessCourse($user, $course);

        $this->assertFalse($result->allowed);
        $this->assertIsArray($result->reasons);
        $this->assertContains('requires_bayah', $result->reasons);
        $this->assertContains('level_too_low', $result->reasons);
        $this->assertContains('gender_mismatch', $result->reasons);
        $this->assertSame('expert', $result->requiredLevel);
        $this->assertSame('male', $result->requiredGender);
        $this->assertTrue($result->requiresBayah);
    }

    /** @test */
    public function allowed_result_has_empty_reasons_and_null_requirements(): void
    {
        $user = User::factory()->create([
            'gender' => 'male',
            'level' => 'expert',
            'has_bayah' => true,
        ]);

        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')
            ->requiresBayah()
            ->withMinLevel('expert')
            ->forGender('male')
            ->create();

        $result = $this->service->canAccessCourse($user, $course);

        $this->assertTrue($result->allowed);
        $this->assertEmpty($result->reasons);
        $this->assertNull($result->requiredLevel);
        $this->assertNull($result->requiredGender);
        $this->assertFalse($result->requiresBayah);
    }
}

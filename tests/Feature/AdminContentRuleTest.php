<?php

namespace Tests\Feature;

use App\Models\ContentRule;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentRuleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_upsert_course_rule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/courses/{$course->id}", [
                'min_level' => 'expert',
                'gender' => 'male',
                'requires_bayah' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_rules', [
            'ruleable_type' => Course::class,
            'ruleable_id' => $course->id,
            'min_level' => 'expert',
            'gender' => 'male',
            'requires_bayah' => true,
        ]);
    }

    /** @test */
    public function admin_can_update_existing_course_rule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->create([
            'min_level' => 'beginner',
            'gender' => null,
            'requires_bayah' => false,
        ]);

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/courses/{$course->id}", [
                'min_level' => 'expert',
                'gender' => 'female',
                'requires_bayah' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_rules', [
            'ruleable_type' => Course::class,
            'ruleable_id' => $course->id,
            'min_level' => 'expert',
            'gender' => 'female',
            'requires_bayah' => true,
        ]);

        // Ensure only one rule exists (update, not create)
        $this->assertEquals(1, ContentRule::where('ruleable_type', Course::class)
            ->where('ruleable_id', $course->id)
            ->count());
    }

    /** @test */
    public function admin_can_upsert_module_rule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $module = Module::factory()->create();

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/modules/{$module->id}", [
                'min_level' => 'intermediate',
                'gender' => null,
                'requires_bayah' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_rules', [
            'ruleable_type' => Module::class,
            'ruleable_id' => $module->id,
            'min_level' => 'intermediate',
            'gender' => null,
            'requires_bayah' => true,
        ]);
    }

    /** @test */
    public function admin_can_upsert_lesson_rule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = Lesson::factory()->create();

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/lessons/{$lesson->id}", [
                'min_level' => null,
                'gender' => 'female',
                'requires_bayah' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_rules', [
            'ruleable_type' => Lesson::class,
            'ruleable_id' => $lesson->id,
            'min_level' => null,
            'gender' => 'female',
            'requires_bayah' => false,
        ]);
    }

    /** @test */
    public function admin_can_delete_rule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        $rule = ContentRule::factory()->for($course, 'ruleable')->create();

        $response = $this->actingAs($admin)
            ->delete("/admin/content-rules/courses/{$course->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('content_rules', [
            'id' => $rule->id,
        ]);
    }

    /** @test */
    public function deleting_nonexistent_rule_does_not_error(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        // No rule exists

        $response = $this->actingAs($admin)
            ->delete("/admin/content-rules/courses/{$course->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function non_admin_cannot_upsert_rule(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $course = Course::factory()->create();

        $response = $this->actingAs($user)
            ->put("/admin/content-rules/courses/{$course->id}", [
                'min_level' => 'expert',
                'gender' => 'male',
                'requires_bayah' => true,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('content_rules', [
            'ruleable_type' => Course::class,
            'ruleable_id' => $course->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_delete_rule(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $course = Course::factory()->create();
        $rule = ContentRule::factory()->for($course, 'ruleable')->create();

        $response = $this->actingAs($user)
            ->delete("/admin/content-rules/courses/{$course->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('content_rules', [
            'id' => $rule->id,
        ]);
    }

    /** @test */
    public function validation_rejects_invalid_min_level(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/courses/{$course->id}", [
                'min_level' => 'pro', // Invalid
                'gender' => 'male',
                'requires_bayah' => false,
            ]);

        $response->assertSessionHasErrors('min_level');

        $this->assertDatabaseMissing('content_rules', [
            'ruleable_type' => Course::class,
            'ruleable_id' => $course->id,
        ]);
    }

    /** @test */
    public function validation_rejects_invalid_gender(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/courses/{$course->id}", [
                'min_level' => 'beginner',
                'gender' => 'other', // Invalid
                'requires_bayah' => false,
            ]);

        $response->assertSessionHasErrors('gender');

        $this->assertDatabaseMissing('content_rules', [
            'ruleable_type' => Course::class,
            'ruleable_id' => $course->id,
        ]);
    }

    /** @test */
    public function validation_accepts_null_values(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/courses/{$course->id}", [
                'min_level' => null,
                'gender' => null,
                'requires_bayah' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_rules', [
            'ruleable_type' => Course::class,
            'ruleable_id' => $course->id,
            'min_level' => null,
            'gender' => null,
            'requires_bayah' => false,
        ]);
    }

    /** @test */
    public function invalid_entity_type_returns_404(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/invalid/1", [
                'min_level' => 'beginner',
                'gender' => null,
                'requires_bayah' => false,
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_edit_course_page_includes_content_rule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->create([
            'min_level' => 'expert',
            'gender' => 'male',
            'requires_bayah' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/courses/{$course->id}/edit");

        $response->assertOk();

        $pageData = $response->viewData('page')['props'];
        $this->assertNotNull($pageData['contentRule']);
        $this->assertSame('expert', $pageData['contentRule']['min_level']);
        $this->assertSame('male', $pageData['contentRule']['gender']);
        $this->assertTrue($pageData['contentRule']['requires_bayah']);
    }

    /** @test */
    public function admin_edit_module_page_includes_content_rule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $module = Module::factory()->create();
        ContentRule::factory()->for($module, 'ruleable')->create([
            'min_level' => 'intermediate',
            'gender' => null,
            'requires_bayah' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/modules/{$module->id}/edit");

        $response->assertOk();

        $pageData = $response->viewData('page')['props'];
        $this->assertNotNull($pageData['contentRule']);
        $this->assertSame('intermediate', $pageData['contentRule']['min_level']);
    }

    /** @test */
    public function admin_edit_lesson_page_includes_content_rule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = Lesson::factory()->create();
        ContentRule::factory()->for($lesson, 'ruleable')->create([
            'min_level' => null,
            'gender' => 'female',
            'requires_bayah' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/lessons/{$lesson->id}/edit");

        $response->assertOk();

        $pageData = $response->viewData('page')['props'];
        $this->assertNotNull($pageData['contentRule']);
        $this->assertSame('female', $pageData['contentRule']['gender']);
        $this->assertTrue($pageData['contentRule']['requires_bayah']);
    }

    /** @test */
    public function admin_edit_pages_show_null_when_no_rule_exists(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        // No rule

        $response = $this->actingAs($admin)
            ->get("/admin/courses/{$course->id}/edit");

        $response->assertOk();

        $pageData = $response->viewData('page')['props'];
        $this->assertNull($pageData['contentRule']);
    }

    /** @test */
    public function update_existing_rule_does_not_create_duplicate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $course = Course::factory()->create();
        ContentRule::factory()->for($course, 'ruleable')->create([
            'min_level' => 'beginner',
            'gender' => null,
            'requires_bayah' => false,
        ]);

        // Update the rule
        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/courses/{$course->id}", [
                'min_level' => 'expert',
                'gender' => 'male',
                'requires_bayah' => true,
            ]);

        $response->assertRedirect();

        // Should still be only one rule
        $this->assertEquals(1, ContentRule::where('ruleable_type', Course::class)
            ->where('ruleable_id', $course->id)
            ->count());

        // Rule should be updated
        $rule = ContentRule::where('ruleable_type', Course::class)
            ->where('ruleable_id', $course->id)
            ->first();
        $this->assertSame('expert', $rule->min_level);
        $this->assertSame('male', $rule->gender);
        $this->assertTrue($rule->requires_bayah);
    }

    /** @test */
    public function invalid_type_param_returns_404(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/invalid-type/1", [
                'min_level' => 'beginner',
                'gender' => null,
                'requires_bayah' => false,
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_can_upsert_module_rule_separately(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $module = Module::factory()->create();

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/modules/{$module->id}", [
                'min_level' => 'intermediate',
                'gender' => 'female',
                'requires_bayah' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_rules', [
            'ruleable_type' => Module::class,
            'ruleable_id' => $module->id,
            'min_level' => 'intermediate',
            'gender' => 'female',
            'requires_bayah' => true,
        ]);
    }

    /** @test */
    public function admin_can_upsert_lesson_rule_separately(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $lesson = Lesson::factory()->create();

        $response = $this->actingAs($admin)
            ->put("/admin/content-rules/lessons/{$lesson->id}", [
                'min_level' => 'expert',
                'gender' => null,
                'requires_bayah' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('content_rules', [
            'ruleable_type' => Lesson::class,
            'ruleable_id' => $lesson->id,
            'min_level' => 'expert',
            'gender' => null,
            'requires_bayah' => false,
        ]);
    }

    /** @test */
    public function non_admin_forbidden_on_module_rule_upsert(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $module = Module::factory()->create();

        $response = $this->actingAs($user)
            ->put("/admin/content-rules/modules/{$module->id}", [
                'min_level' => 'beginner',
                'gender' => null,
                'requires_bayah' => false,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_forbidden_on_lesson_rule_upsert(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $lesson = Lesson::factory()->create();

        $response = $this->actingAs($user)
            ->put("/admin/content-rules/lessons/{$lesson->id}", [
                'min_level' => 'beginner',
                'gender' => null,
                'requires_bayah' => false,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_forbidden_on_module_rule_delete(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $module = Module::factory()->create();
        $rule = ContentRule::factory()->for($module, 'ruleable')->create();

        $response = $this->actingAs($user)
            ->delete("/admin/content-rules/modules/{$module->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_forbidden_on_lesson_rule_delete(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $lesson = Lesson::factory()->create();
        $rule = ContentRule::factory()->for($lesson, 'ruleable')->create();

        $response = $this->actingAs($user)
            ->delete("/admin/content-rules/lessons/{$lesson->id}");

        $response->assertStatus(403);
    }
}

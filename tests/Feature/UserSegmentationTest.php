<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSegmentationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function student_can_update_own_gender_and_whatsapp_fields(): void
    {
        $user = User::factory()->create([
            'gender' => null,
            'whatsapp_number' => null,
            'whatsapp_opt_in' => false,
        ]);

        $response = $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'gender' => 'male',
                'whatsapp_number' => '+1234567890',
                'whatsapp_opt_in' => true,
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('male', $user->gender);
        $this->assertSame('+1234567890', $user->whatsapp_number);
        $this->assertTrue($user->whatsapp_opt_in);
    }

    /** @test */
    public function student_cannot_update_has_bayah_field(): void
    {
        $user = User::factory()->create(['has_bayah' => false]);

        $response = $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'has_bayah' => true, // This should be ignored
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        // has_bayah should remain false (not updated by student)
        $this->assertFalse($user->has_bayah);
    }

    /** @test */
    public function student_cannot_update_level_field(): void
    {
        $user = User::factory()->create(['level' => 'beginner']);

        $response = $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'level' => 'expert', // This should be ignored
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        // level should remain 'beginner' (not updated by student)
        $this->assertSame('beginner', $user->level);
    }

    /** @test */
    public function whatsapp_opt_in_requires_whatsapp_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp_opt_in' => true,
                'whatsapp_number' => '', // Empty
            ]);

        $response->assertSessionHasErrors('whatsapp_opt_in');
    }

    /** @test */
    public function admin_can_update_bayah_and_level_for_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'has_bayah' => false,
            'level' => 'beginner',
        ]);

        $response = $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/segmentation", [
                'has_bayah' => true,
                'level' => 'intermediate',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->has_bayah);
        $this->assertSame('intermediate', $user->level);
    }

    /** @test */
    public function admin_can_update_gender_for_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['gender' => null]);

        $response = $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/segmentation", [
                'has_bayah' => false,
                'level' => 'beginner',
                'gender' => 'female',
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('female', $user->gender);
    }

    /** @test */
    public function non_admin_cannot_access_admin_segmentation_route(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch("/admin/users/{$targetUser->id}/segmentation", [
                'has_bayah' => true,
                'level' => 'expert',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function last_active_at_updates_on_authenticated_request(): void
    {
        $user = User::factory()->create(['last_active_at' => null]);

        // Make an authenticated request
        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $user->refresh();
        $this->assertNotNull($user->last_active_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->last_active_at);
    }

    /** @test */
    public function last_active_at_updates_on_every_authenticated_request(): void
    {
        $user = User::factory()->create();
        $initialActiveAt = $user->last_active_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Make another authenticated request
        $response = $this->actingAs($user)
            ->get('/profile');

        $response->assertOk();

        $user->refresh();
        $this->assertNotEquals($initialActiveAt, $user->last_active_at);
        $this->assertTrue($user->last_active_at->gt($initialActiveAt));
    }

    /** @test */
    public function admin_segmentation_requires_valid_level(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/segmentation", [
                'has_bayah' => false,
                'level' => 'invalid-level',
            ]);

        $response->assertSessionHasErrors('level');
    }

    /** @test */
    public function admin_segmentation_requires_has_bayah_field(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/segmentation", [
                'level' => 'beginner',
                // has_bayah missing
            ]);

        $response->assertSessionHasErrors('has_bayah');
    }

    /** @test */
    public function admin_segmentation_requires_level_field(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/segmentation", [
                'has_bayah' => false,
                // level missing
            ]);

        $response->assertSessionHasErrors('level');
    }
}

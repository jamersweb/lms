<?php

namespace Tests\Feature;

use App\Models\DuaPrayer;
use App\Models\DuaRequest;
use App\Models\ModerationAction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuaWallTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_dua_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dua.store'), [
            'content' => 'Please make dua for my exams.',
            'is_anonymous' => false,
        ]);

        $response->assertRedirect(route('dua.index'));

        $this->assertDatabaseHas('dua_requests', [
            'content' => 'Please make dua for my exams.',
            'user_id' => $user->id,
            'is_anonymous' => false,
            'status' => 'active',
        ]);
    }

    public function test_rate_limiting_works(): void
    {
        $user = User::factory()->create();
        Carbon::setTestNow('2026-01-30 12:00:00');

        // Create 3 requests today
        for ($i = 1; $i <= 3; $i++) {
            DuaRequest::create([
                'user_id' => $user->id,
                'content' => "Dua request number {$i}",
                'status' => 'active',
                'created_at' => Carbon::today(),
            ]);
        }

        // 4th request should be rejected
        $response = $this->actingAs($user)->post(route('dua.store'), [
            'content' => 'This should be rejected',
            'is_anonymous' => false,
        ]);

        $response->assertSessionHasErrors('content');
        $this->assertDatabaseMissing('dua_requests', [
            'content' => 'This should be rejected',
        ]);
    }

    public function test_prayed_for_you_increments_once_per_user(): void
    {
        $user = User::factory()->create();

        $dua = DuaRequest::create([
            'user_id' => null,
            'is_anonymous' => true,
            'content' => 'Anonymous dua',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('dua.pray', $dua))
            ->assertRedirect(route('dua.index'));

        $this->assertDatabaseHas('dua_prayers', [
            'dua_request_id' => $dua->id,
            'user_id' => $user->id,
        ]);

        // Second attempt should not create a duplicate
        $this->actingAs($user)->post(route('dua.pray', $dua))
            ->assertRedirect(route('dua.index'));

        $this->assertEquals(1, DuaPrayer::where('dua_request_id', $dua->id)->where('user_id', $user->id)->count());
    }

    public function test_dua_wall_index_returns_has_prayed_flag(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $dua = DuaRequest::create([
            'user_id' => $otherUser->id,
            'content' => 'Test dua request',
            'status' => 'active',
        ]);

        // User has not prayed yet
        $response = $this->actingAs($user)->get(route('dua.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dua/Index')
            ->has('requests.data', 1)
            ->where('requests.data.0.has_prayed', false)
        );

        // User prays
        DuaPrayer::create([
            'dua_request_id' => $dua->id,
            'user_id' => $user->id,
        ]);

        // Now has_prayed should be true
        $response = $this->actingAs($user)->get(route('dua.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dua/Index')
            ->has('requests.data', 1)
            ->where('requests.data.0.has_prayed', true)
        );
    }

    public function test_hidden_requests_not_visible_to_students(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $activeDua = DuaRequest::create([
            'user_id' => $user->id,
            'content' => 'Active dua request',
            'status' => 'active',
        ]);

        $hiddenDua = DuaRequest::create([
            'user_id' => $user->id,
            'content' => 'Hidden dua request',
            'status' => 'hidden',
            'hidden_by' => $admin->id,
            'hidden_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dua.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dua/Index')
            ->has('requests.data', 1)
            ->where('requests.data.0.id', $activeDua->id)
        );
    }

    public function test_admin_can_hide_dua_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dua = DuaRequest::create([
            'user_id' => User::factory()->create()->id,
            'content' => 'Test dua',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.dua-wall.hide', ['dua' => $dua->id]));

        $response->assertRedirect();
        $this->assertDatabaseHas('dua_requests', [
            'id' => $dua->id,
            'status' => 'hidden',
            'hidden_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $admin->id,
            'target_type' => 'dua_request',
            'target_id' => $dua->id,
            'action' => 'hide',
        ]);
    }

    public function test_admin_can_unhide_dua_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dua = DuaRequest::create([
            'user_id' => User::factory()->create()->id,
            'content' => 'Test dua',
            'status' => 'hidden',
            'hidden_by' => $admin->id,
            'hidden_at' => now(),
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.dua-wall.unhide', ['dua' => $dua->id]));

        $response->assertRedirect();
        $dua->refresh();
        $this->assertEquals('active', $dua->status);
        $this->assertNull($dua->hidden_by);
        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $admin->id,
            'target_type' => 'dua_request',
            'target_id' => $dua->id,
            'action' => 'unhide',
        ]);
    }

    public function test_admin_can_delete_dua_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dua = DuaRequest::create([
            'user_id' => User::factory()->create()->id,
            'content' => 'Test dua',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.dua-wall.destroy', ['dua' => $dua->id]));

        $response->assertRedirect();
        $this->assertSoftDeleted('dua_requests', ['id' => $dua->id]);
        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $admin->id,
            'target_type' => 'dua_request',
            'target_id' => $dua->id,
            'action' => 'delete',
        ]);
    }

    public function test_admin_can_restore_deleted_dua_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dua = DuaRequest::create([
            'user_id' => User::factory()->create()->id,
            'content' => 'Test dua',
            'status' => 'active',
        ]);
        $dua->delete();

        $response = $this->actingAs($admin)->patch(route('admin.dua-wall.restore', ['id' => $dua->id]));

        $response->assertRedirect();
        $this->assertDatabaseHas('dua_requests', [
            'id' => $dua->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $admin->id,
            'target_type' => 'dua_request',
            'target_id' => $dua->id,
            'action' => 'restore',
        ]);
    }

    public function test_non_admin_cannot_moderate_dua_requests(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $dua = DuaRequest::create([
            'user_id' => $user->id,
            'content' => 'Test dua',
            'status' => 'active',
        ]);

        // Try to hide
        $this->actingAs($user)->patch(route('admin.dua-wall.hide', ['dua' => $dua->id]))
            ->assertStatus(403);

        // Try to delete
        $this->actingAs($user)->delete(route('admin.dua-wall.destroy', ['dua' => $dua->id]))
            ->assertStatus(403);

        // Try to restore
        $dua->delete();
        $this->actingAs($user)->patch(route('admin.dua-wall.restore', ['id' => $dua->id]))
            ->assertStatus(403);
    }

    public function test_validation_content_min_max(): void
    {
        $user = User::factory()->create();

        // Too short
        $response = $this->actingAs($user)->post(route('dua.store'), [
            'content' => 'Short',
            'is_anonymous' => false,
        ]);
        $response->assertSessionHasErrors('content');

        // Too long
        $response = $this->actingAs($user)->post(route('dua.store'), [
            'content' => str_repeat('a', 2001),
            'is_anonymous' => false,
        ]);
        $response->assertSessionHasErrors('content');

        // Valid length
        $response = $this->actingAs($user)->post(route('dua.store'), [
            'content' => str_repeat('a', 100),
            'is_anonymous' => false,
        ]);
        $response->assertRedirect(route('dua.index'));
    }
}

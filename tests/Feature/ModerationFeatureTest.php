<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Discussion;
use App\Models\DiscussionReply;

class ModerationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_moderation_panel()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get(route('admin.moderation.index'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_moderation_panel()
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)->get(route('admin.moderation.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_discussion()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $discussion = Discussion::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.moderation.handle'), [
            'target_type' => 'discussion',
            'target_id' => $discussion->id,
            'action' => 'delete',
            'reason' => 'Spam',
        ]);

        $response->assertRedirect();
        $this->assertSoftDeleted('discussions', ['id' => $discussion->id]);
        $this->assertDatabaseHas('moderation_actions', [
            'moderator_id' => $admin->id,
            'action' => 'delete',
            'target_id' => $discussion->id,
        ]);
    }

    public function test_admin_can_lock_discussion()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $discussion = Discussion::factory()->create(['status' => 'open']);

        $response = $this->actingAs($admin)->post(route('admin.moderation.handle'), [
            'target_type' => 'discussion',
            'target_id' => $discussion->id,
            'action' => 'lock',
        ]);

        $response->assertRedirect();
        $this->assertEquals('closed', $discussion->fresh()->status);
    }
}

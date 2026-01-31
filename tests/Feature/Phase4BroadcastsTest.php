<?php

namespace Tests\Feature;

use App\Jobs\SendBroadcastJob;
use App\Models\Broadcast;
use App\Models\BroadcastDelivery;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\BroadcastEmailNotification;
use App\Notifications\BroadcastInAppNotification;
use App\Services\BroadcastAudienceService;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class Phase4BroadcastsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function admin_can_create_broadcast_draft()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->post('/admin/broadcasts', [
                'title' => 'Test Broadcast',
                'body' => 'This is a test message',
                'channels' => ['email', 'in_app'],
                'audience_filters' => [
                    'min_level' => 'expert',
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('broadcasts', [
            'title' => 'Test Broadcast',
            'status' => Broadcast::STATUS_DRAFT,
        ]);
    }

    /** @test */
    public function audience_preview_returns_correct_count()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Create users with different levels
        $beginner = User::factory()->create(['level' => 'beginner']);
        $expert = User::factory()->create(['level' => 'expert']);

        $response = $this->actingAs($admin)
            ->post('/admin/broadcasts/preview', [
                'channels' => ['email'],
                'audience_filters' => [
                    'min_level' => 'expert',
                ],
            ]);

        $response->assertOk();
        $data = $response->json();

        // Should only count expert user
        $this->assertEquals(1, $data['total_count']);
        $this->assertCount(1, $data['sample']);
        $this->assertEquals($expert->id, $data['sample'][0]['id']);
    }

    /** @test */
    public function audience_preview_respects_channel_opt_ins()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $userWithEmail = User::factory()->create([
            'level' => 'expert',
            'email_reminders_opt_in' => true,
        ]);

        $userWithoutEmail = User::factory()->create([
            'level' => 'expert',
            'email_reminders_opt_in' => false,
        ]);

        $response = $this->actingAs($admin)
            ->post('/admin/broadcasts/preview', [
                'channels' => ['email'],
                'audience_filters' => [
                    'min_level' => 'expert',
                ],
            ]);

        $response->assertOk();
        $data = $response->json();

        // Total count should be 2 (both experts)
        $this->assertEquals(2, $data['total_count']);
        // But email channel count should be 1 (only opted-in)
        $this->assertEquals(1, $data['channel_counts']['email']);
    }

    /** @test */
    public function audience_preview_returns_max_20_samples()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Create 25 expert users
        User::factory()->count(25)->create(['level' => 'expert']);

        $response = $this->actingAs($admin)
            ->post('/admin/broadcasts/preview', [
                'channels' => ['email'],
                'audience_filters' => [
                    'min_level' => 'expert',
                ],
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(25, $data['total_count']);
        $this->assertCount(20, $data['sample']); // Max 20 samples
    }

    /** @test */
    public function send_broadcast_queues_job()
    {
        Queue::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['email'],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/broadcasts/{$broadcast->id}/send");

        $response->assertRedirect();
        Queue::assertPushed(SendBroadcastJob::class, function ($job) use ($broadcast) {
            return $job->broadcastId === $broadcast->id;
        });
    }

    /** @test */
    public function send_broadcast_job_creates_deliveries()
    {
        Notification::fake();

        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['email', 'in_app'],
            'audience_filters' => [],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $user1 = User::factory()->create(['email_reminders_opt_in' => true]);
        $user2 = User::factory()->create(['email_reminders_opt_in' => true]);

        $job = new SendBroadcastJob($broadcast->id);
        $job->handle();

        // Should create deliveries for both users and both channels
        $deliveriesCount = BroadcastDelivery::where('broadcast_id', $broadcast->id)
            ->whereIn('user_id', [$user1->id, $user2->id])
            ->count();
        $this->assertEquals(4, $deliveriesCount); // 2 users × 2 channels

        // Check deliveries exist
        $this->assertDatabaseHas('broadcast_deliveries', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $user1->id,
            'channel' => 'email',
            'status' => BroadcastDelivery::STATUS_SENT,
        ]);

        $this->assertDatabaseHas('broadcast_deliveries', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $user1->id,
            'channel' => 'in_app',
            'status' => BroadcastDelivery::STATUS_SENT,
        ]);
    }

    /** @test */
    public function send_broadcast_respects_email_opt_out()
    {
        Notification::fake();

        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['email'],
            'audience_filters' => [],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $optedIn = User::factory()->create(['email_reminders_opt_in' => true]);
        $optedOut = User::factory()->create(['email_reminders_opt_in' => false]);

        $job = new SendBroadcastJob($broadcast->id);
        $job->handle();

        // Should only send to opted-in user
        Notification::assertSentTo($optedIn, BroadcastEmailNotification::class);
        Notification::assertNotSentTo($optedOut, BroadcastEmailNotification::class);

        // Delivery should be skipped for opted-out
        $this->assertDatabaseHas('broadcast_deliveries', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $optedOut->id,
            'channel' => 'email',
            'status' => BroadcastDelivery::STATUS_SKIPPED,
        ]);
    }

    /** @test */
    public function send_broadcast_respects_whatsapp_opt_out()
    {
        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['whatsapp'],
            'audience_filters' => [],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $optedIn = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        $optedOut = User::factory()->create([
            'whatsapp_opt_in' => false,
            'whatsapp_number' => '+0987654321',
        ]);

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('sendMessage')->once()->with('+1234567890', Mockery::any());
        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendBroadcastJob($broadcast->id);
        $job->handle();

        // Delivery should be skipped for opted-out
        $this->assertDatabaseHas('broadcast_deliveries', [
            'broadcast_id' => $broadcast->id,
            'user_id' => $optedOut->id,
            'channel' => 'whatsapp',
            'status' => BroadcastDelivery::STATUS_SKIPPED,
        ]);
    }

    /** @test */
    public function dedupe_key_prevents_duplicate_deliveries()
    {
        Notification::fake();

        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['email'],
            'audience_filters' => [],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $user = User::factory()->create(['email_reminders_opt_in' => true]);

        // Run job twice
        $job1 = new SendBroadcastJob($broadcast->id);
        $job1->handle();

        // Update broadcast back to draft to allow re-sending
        $broadcast->update(['status' => Broadcast::STATUS_DRAFT]);

        $job2 = new SendBroadcastJob($broadcast->id);
        $job2->handle();

        // Should only have one delivery per user/channel
        $this->assertEquals(1, BroadcastDelivery::where('broadcast_id', $broadcast->id)
            ->where('user_id', $user->id)
            ->where('channel', 'email')
            ->count());
    }

    /** @test */
    public function failed_delivery_is_logged_with_error()
    {
        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['whatsapp'],
            'audience_filters' => [],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('sendMessage')->once()->andThrow(new \Exception('Provider error'));
        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendBroadcastJob($broadcast->id);
        $job->handle();

        $delivery = BroadcastDelivery::where('broadcast_id', $broadcast->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($delivery);
        $this->assertEquals(BroadcastDelivery::STATUS_FAILED, $delivery->status);
        $this->assertNotNull($delivery->error);
        $this->assertStringContainsString('Provider error', $delivery->error);
    }

    /** @test */
    public function in_app_notifications_are_stored()
    {
        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['in_app'],
            'audience_filters' => [],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $user = User::factory()->create();

        $job = new SendBroadcastJob($broadcast->id);
        $job->handle();

        // Check notification was created
        $notification = $user->notifications()
            ->where('type', BroadcastInAppNotification::class)
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals($broadcast->id, $notification->data['broadcast_id']);
        $this->assertEquals($broadcast->title, $notification->data['title']);
    }

    /** @test */
    public function inbox_displays_broadcasts()
    {
        $user = User::factory()->create();

        $broadcast = Broadcast::create([
            'title' => 'Test Broadcast',
            'body' => 'Test body',
            'channels' => ['in_app'],
            'status' => Broadcast::STATUS_SENT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        // Create delivery and notification
        BroadcastDelivery::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $user->id,
            'channel' => 'in_app',
            'status' => BroadcastDelivery::STATUS_SENT,
            'dedupe_key' => sha1("broadcast:{$broadcast->id}:user:{$user->id}:channel:in_app"),
        ]);

        $user->notify(new BroadcastInAppNotification($broadcast));

        $response = $this->actingAs($user)
            ->get('/inbox');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Inbox/Index')
            ->has('broadcasts', 1)
            ->where('broadcasts.0.title', 'Test Broadcast')
        );
    }

    /** @test */
    public function viewing_broadcast_marks_it_as_read()
    {
        $user = User::factory()->create();

        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['in_app'],
            'status' => Broadcast::STATUS_SENT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        BroadcastDelivery::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $user->id,
            'channel' => 'in_app',
            'status' => BroadcastDelivery::STATUS_SENT,
            'dedupe_key' => sha1("broadcast:{$broadcast->id}:user:{$user->id}:channel:in_app"),
        ]);

        $notification = $user->notify(new BroadcastInAppNotification($broadcast));
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->get("/inbox/{$broadcast->id}");

        $response->assertOk();
        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function broadcast_status_updates_to_sent_after_job()
    {
        Notification::fake();

        $broadcast = Broadcast::create([
            'title' => 'Test',
            'body' => 'Test body',
            'channels' => ['email'],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        User::factory()->create(['email_reminders_opt_in' => true]);

        $job = new SendBroadcastJob($broadcast->id);
        $job->handle();

        $broadcast->refresh();
        $this->assertEquals(Broadcast::STATUS_SENT, $broadcast->status);
        $this->assertNotNull($broadcast->sent_at);
    }
}

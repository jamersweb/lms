<?php

namespace Tests\Feature;

use App\Jobs\SendMicroNudgeJob;
use App\Models\AudioClip;
use App\Models\MicroNudgeCampaign;
use App\Models\MicroNudgeDelivery;
use App\Models\User;
use App\Services\AudienceFilterService;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class Phase4MicroNudgesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function hourly_campaign_triggers_only_at_correct_minute()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Hourly Test',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 15,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        // Freeze time at minute 15
        Carbon::setTestNow(Carbon::now()->setMinute(15)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->once();

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendMicroNudgeJob();
        $job->handle();

        $this->assertDatabaseHas('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'status' => MicroNudgeDelivery::STATUS_SENT,
        ]);

        // Reset time to minute 30
        Carbon::setTestNow(Carbon::now()->setMinute(30)->setSecond(0));

        // Clear previous deliveries for this test
        MicroNudgeDelivery::truncate();

        $provider2 = Mockery::mock(WhatsAppProviderInterface::class);
        $provider2->shouldNotReceive('sendMessage');

        $this->app->instance(WhatsAppProviderInterface::class, $provider2);

        $job2 = new SendMicroNudgeJob();
        $job2->handle();

        // Should not send at minute 30
        $this->assertDatabaseMissing('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function daily_campaign_triggers_at_correct_time()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Daily Test',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_DAILY,
            'send_hour' => 14,
            'send_minute' => 30,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        // Freeze time at 14:30
        Carbon::setTestNow(Carbon::now()->setHour(14)->setMinute(30)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->once();

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendMicroNudgeJob();
        $job->handle();

        $this->assertDatabaseHas('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'status' => MicroNudgeDelivery::STATUS_SENT,
        ]);

        // Reset time to 15:00
        Carbon::setTestNow(Carbon::now()->setHour(15)->setMinute(0)->setSecond(0));

        MicroNudgeDelivery::truncate();

        $provider2 = Mockery::mock(WhatsAppProviderInterface::class);
        $provider2->shouldNotReceive('sendMessage');

        $this->app->instance(WhatsAppProviderInterface::class, $provider2);

        $job2 = new SendMicroNudgeJob();
        $job2->handle();

        // Should not send at 15:00
        $this->assertDatabaseMissing('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function segmentation_filters_users_by_level()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Expert Only',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 0,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
            'audience_filters' => [
                'min_level' => 'expert',
            ],
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $beginnerUser = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1111111111',
            'level' => 'beginner',
        ]);

        $expertUser = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+2222222222',
            'level' => 'expert',
        ]);

        Carbon::setTestNow(Carbon::now()->setMinute(0)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->once()->with('+2222222222', Mockery::any());

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendMicroNudgeJob();
        $job->handle();

        // Only expert user should receive
        $this->assertDatabaseHas('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $expertUser->id,
        ]);

        $this->assertDatabaseMissing('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $beginnerUser->id,
        ]);
    }

    /** @test */
    public function segmentation_filters_users_by_bayah()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Bayah Required',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 0,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
            'audience_filters' => [
                'requires_bayah' => true,
            ],
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $noBayahUser = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1111111111',
            'has_bayah' => false,
        ]);

        $bayahUser = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+2222222222',
            'has_bayah' => true,
        ]);

        Carbon::setTestNow(Carbon::now()->setMinute(0)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->once()->with('+2222222222', Mockery::any());

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendMicroNudgeJob();
        $job->handle();

        // Only bayah user should receive
        $this->assertDatabaseHas('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $bayahUser->id,
        ]);

        $this->assertDatabaseMissing('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $noBayahUser->id,
        ]);
    }

    /** @test */
    public function segmentation_filters_users_by_gender()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Female Only',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 0,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
            'audience_filters' => [
                'gender' => 'female',
            ],
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $maleUser = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1111111111',
            'gender' => 'male',
        ]);

        $femaleUser = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+2222222222',
            'gender' => 'female',
        ]);

        Carbon::setTestNow(Carbon::now()->setMinute(0)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->once()->with('+2222222222', Mockery::any());

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendMicroNudgeJob();
        $job->handle();

        // Only female user should receive
        $this->assertDatabaseHas('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $femaleUser->id,
        ]);

        $this->assertDatabaseMissing('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $maleUser->id,
        ]);
    }

    /** @test */
    public function dedupe_key_prevents_duplicate_deliveries()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Dedupe Test',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 0,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        Carbon::setTestNow(Carbon::now()->setMinute(0)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->once(); // Only once

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        // Run job twice in same hour
        $job1 = new SendMicroNudgeJob();
        $job1->handle();

        $job2 = new SendMicroNudgeJob();
        $job2->handle();

        // Should only have one delivery
        $this->assertEquals(1, MicroNudgeDelivery::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->count());
    }

    /** @test */
    public function provider_calls_sendAudio_when_supportsAudio_is_true()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Audio Test',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 0,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        Carbon::setTestNow(Carbon::now()->setMinute(0)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(true);
        $provider->shouldReceive('sendAudio')->once()->with(
            '+1234567890',
            Mockery::type('string'), // URL
            Mockery::type('string')  // Caption
        );
        $provider->shouldNotReceive('sendMessage');

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendMicroNudgeJob();
        $job->handle();

        $this->assertDatabaseHas('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'status' => MicroNudgeDelivery::STATUS_SENT,
        ]);
    }

    /** @test */
    public function provider_calls_sendMessage_when_supportsAudio_is_false()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Message Fallback Test',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 0,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        Carbon::setTestNow(Carbon::now()->setMinute(0)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->once()->with(
            '+1234567890',
            Mockery::on(function ($message) {
                return str_contains($message, 'Sunnah of the Hour') && str_contains($message, 'https://example.com/audio.mp3');
            })
        );
        $provider->shouldNotReceive('sendAudio');

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendMicroNudgeJob();
        $job->handle();

        $this->assertDatabaseHas('micro_nudge_deliveries', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'status' => MicroNudgeDelivery::STATUS_SENT,
        ]);
    }

    /** @test */
    public function failed_delivery_is_logged_with_error()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Failure Test',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 0,
            'rotation' => MicroNudgeCampaign::ROTATION_RANDOM,
        ]);

        $clip = AudioClip::create([
            'title' => 'Test Clip',
            'source_type' => 'url',
            'external_url' => 'https://example.com/audio.mp3',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        Carbon::setTestNow(Carbon::now()->setMinute(0)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->once()->andThrow(new \Exception('Provider error'));

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        $job = new SendMicroNudgeJob();
        $job->handle();

        $delivery = MicroNudgeDelivery::where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($delivery);
        $this->assertEquals(MicroNudgeDelivery::STATUS_FAILED, $delivery->status);
        $this->assertNotNull($delivery->error);
        $this->assertStringContainsString('Provider error', $delivery->error);
    }

    /** @test */
    public function sequence_rotation_updates_last_sent_clip_id()
    {
        $campaign = MicroNudgeCampaign::create([
            'name' => 'Sequence Test',
            'is_enabled' => true,
            'schedule_type' => MicroNudgeCampaign::SCHEDULE_HOURLY,
            'send_minute' => 0,
            'rotation' => MicroNudgeCampaign::ROTATION_SEQUENCE,
            'clip_ids' => [1, 2, 3],
        ]);

        $clip1 = AudioClip::create([
            'id' => 1,
            'title' => 'Clip 1',
            'source_type' => 'url',
            'external_url' => 'https://example.com/1.mp3',
            'is_active' => true,
        ]);

        $clip2 = AudioClip::create([
            'id' => 2,
            'title' => 'Clip 2',
            'source_type' => 'url',
            'external_url' => 'https://example.com/2.mp3',
            'is_active' => true,
        ]);

        $clip3 = AudioClip::create([
            'id' => 3,
            'title' => 'Clip 3',
            'source_type' => 'url',
            'external_url' => 'https://example.com/3.mp3',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'whatsapp_opt_in' => true,
            'whatsapp_number' => '+1234567890',
        ]);

        Carbon::setTestNow(Carbon::now()->setMinute(0)->setSecond(0));

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('supportsAudio')->andReturn(false);
        $provider->shouldReceive('sendMessage')->times(3);

        $this->app->instance(WhatsAppProviderInterface::class, $provider);

        // First run - should send clip 1
        $job1 = new SendMicroNudgeJob();
        $job1->handle();
        $campaign->refresh();
        $this->assertEquals(1, $campaign->last_sent_clip_id);

        // Second run (next hour) - should send clip 2
        Carbon::setTestNow(Carbon::now()->addHour()->setMinute(0)->setSecond(0));
        MicroNudgeDelivery::truncate();
        $job2 = new SendMicroNudgeJob();
        $job2->handle();
        $campaign->refresh();
        $this->assertEquals(2, $campaign->last_sent_clip_id);

        // Third run (next hour) - should send clip 3
        Carbon::setTestNow(Carbon::now()->addHour()->setMinute(0)->setSecond(0));
        MicroNudgeDelivery::truncate();
        $job3 = new SendMicroNudgeJob();
        $job3->handle();
        $campaign->refresh();
        $this->assertEquals(3, $campaign->last_sent_clip_id);
    }
}

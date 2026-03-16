<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WhatsAppTriggerEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriggerQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_claim_mark_sent_and_mark_failed_trigger_events(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $pendingTrigger = WhatsAppTriggerEvent::create([
            'event_key' => 'first_login',
            'template_name' => 'first_login',
            'user_name' => 'Test User',
            'phone' => '923001112233',
            'message' => 'Welcome message',
            'status' => WhatsAppTriggerEvent::STATUS_PENDING,
            'language' => 'en',
        ]);

        $failedTrigger = WhatsAppTriggerEvent::create([
            'event_key' => 'video_completion',
            'template_name' => 'video_completion',
            'user_name' => 'Second User',
            'phone' => '923009998887',
            'message' => 'Completion message',
            'status' => WhatsAppTriggerEvent::STATUS_PROCESSING,
            'language' => 'en',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.triggers.claim', $pendingTrigger))
            ->assertRedirect();

        $this->assertDatabaseHas('whatsapp_trigger_events', [
            'id' => $pendingTrigger->id,
            'status' => WhatsAppTriggerEvent::STATUS_PROCESSING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.triggers.mark-sent', $pendingTrigger))
            ->assertRedirect();

        $this->assertDatabaseHas('whatsapp_trigger_events', [
            'id' => $pendingTrigger->id,
            'status' => WhatsAppTriggerEvent::STATUS_SENT,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.triggers.mark-failed', $failedTrigger), [
                'error_message' => 'WhatsApp Web failed to load.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('whatsapp_trigger_events', [
            'id' => $failedTrigger->id,
            'status' => WhatsAppTriggerEvent::STATUS_FAILED,
            'attempts' => 1,
            'error_message' => 'WhatsApp Web failed to load.',
        ]);
    }

    public function test_admin_can_view_next_trigger_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        WhatsAppTriggerEvent::create([
            'event_key' => 'course_enrolment',
            'template_name' => 'course_enrolment',
            'user_name' => 'Queue User',
            'phone' => '923441112233',
            'message' => 'Enrollment message',
            'status' => WhatsAppTriggerEvent::STATUS_PENDING,
            'language' => 'en',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.triggers.next'))
            ->assertStatus(200);
    }
}


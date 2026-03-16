<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppTriggerEvent;
use Illuminate\Support\Facades\Log;

class TriggerOutboxService
{
    /**
     * Queue a single trigger event for OpenClaw pickup.
     */
    public function queue(
        string $eventKey,
        string $templateName,
        User $user,
        string $message,
        array $context = [],
        ?string $idempotencyKey = null
    ): ?WhatsAppTriggerEvent {
        if (!$user->whatsapp_opt_in || empty($user->whatsapp_number)) {
            return null;
        }

        $payload = [
            'event_key' => $eventKey,
            'template_name' => $templateName,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'phone' => $user->whatsapp_number,
            'message' => $message,
            'language' => $context['language'] ?? 'en',
            'context' => $context,
            'status' => WhatsAppTriggerEvent::STATUS_PENDING,
            'idempotency_key' => $idempotencyKey,
        ];

        if (!empty($idempotencyKey)) {
            $event = WhatsAppTriggerEvent::firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                $payload
            );

            return $event->wasRecentlyCreated ? $event : null;
        }

        return WhatsAppTriggerEvent::create($payload);
    }

    /**
     * Queue a trigger event for each eligible user.
     */
    public function queueBulk(
        string $eventKey,
        string $templateName,
        iterable $users,
        string $message,
        array $context = []
    ): int {
        $count = 0;

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $event = $this->queue(
                eventKey: $eventKey,
                templateName: $templateName,
                user: $user,
                message: $message,
                context: $context,
            );

            if ($event) {
                $count++;
            }
        }

        Log::info('TriggerOutboxService: queued bulk events', [
            'event_key' => $eventKey,
            'queued_count' => $count,
        ]);

        return $count;
    }
}


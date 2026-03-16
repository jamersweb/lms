<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTriggerEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenClawTriggerController extends Controller
{
    /**
     * Read-only pending list.
     */
    public function pending(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->integer('limit', 50), 200));

        $events = WhatsAppTriggerEvent::query()
            ->where('status', WhatsAppTriggerEvent::STATUS_PENDING)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $events->map(fn (WhatsAppTriggerEvent $event) => $this->transform($event)),
        ]);
    }

    /**
     * Claim a batch atomically so multiple workers do not process same rows.
     */
    public function claim(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->integer('limit', 50), 200));
        $worker = (string) $request->input('worker', 'openclaw');
        $ids = [];

        DB::transaction(function () use (&$ids, $limit, $worker): void {
            $ids = WhatsAppTriggerEvent::query()
                ->where('status', WhatsAppTriggerEvent::STATUS_PENDING)
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if (!empty($ids)) {
                WhatsAppTriggerEvent::query()
                    ->whereIn('id', $ids)
                    ->update([
                        'status' => WhatsAppTriggerEvent::STATUS_PROCESSING,
                        'claimed_at' => now(),
                        'claimed_by' => $worker,
                        'updated_at' => now(),
                    ]);
            }
        });

        if (empty($ids)) {
            return response()->json(['data' => []]);
        }

        $events = WhatsAppTriggerEvent::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $events->map(fn (WhatsAppTriggerEvent $event) => $this->transform($event)),
        ]);
    }

    /**
     * Mark trigger as sent by worker.
     */
    public function ack(Request $request, WhatsAppTriggerEvent $trigger): JsonResponse
    {
        $validated = $request->validate([
            'external_id' => ['nullable', 'string', 'max:191'],
        ]);

        $trigger->update([
            'status' => WhatsAppTriggerEvent::STATUS_SENT,
            'processed_at' => now(),
            'external_id' => $validated['external_id'] ?? null,
            'error_message' => null,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $trigger->id,
            'status' => $trigger->status,
        ]);
    }

    /**
     * Mark trigger as failed by worker.
     */
    public function fail(Request $request, WhatsAppTriggerEvent $trigger): JsonResponse
    {
        $validated = $request->validate([
            'error' => ['required', 'string', 'max:2000'],
        ]);

        $trigger->update([
            'status' => WhatsAppTriggerEvent::STATUS_FAILED,
            'processed_at' => now(),
            'attempts' => $trigger->attempts + 1,
            'error_message' => $validated['error'],
        ]);

        return response()->json([
            'ok' => true,
            'id' => $trigger->id,
            'status' => $trigger->status,
        ]);
    }

    private function transform(WhatsAppTriggerEvent $event): array
    {
        return [
            'id' => $event->id,
            'event_key' => $event->event_key,
            'template_name' => $event->template_name,
            'user_id' => $event->user_id,
            'user_name' => $event->user_name,
            'phone' => $event->phone,
            'message' => $event->message,
            'language' => $event->language,
            'status' => $event->status,
            'attempts' => $event->attempts,
            'created_at' => $event->created_at?->toIso8601String(),
        ];
    }
}


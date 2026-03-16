<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTriggerEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TriggerEventController extends Controller
{
    public function index(): Response
    {
        $events = WhatsAppTriggerEvent::query()
            ->with('user:id,name,email')
            ->orderByRaw("
                case
                    when status = 'pending' then 0
                    when status = 'processing' then 1
                    when status = 'failed' then 2
                    when status = 'sent' then 3
                    else 4
                end
            ")
            ->orderBy('id')
            ->paginate(50)
            ->through(function (WhatsAppTriggerEvent $event) {
                return $this->transformEvent($event);
            });

        $stats = [
            'pending' => WhatsAppTriggerEvent::where('status', WhatsAppTriggerEvent::STATUS_PENDING)->count(),
            'processing' => WhatsAppTriggerEvent::where('status', WhatsAppTriggerEvent::STATUS_PROCESSING)->count(),
            'sent_today' => WhatsAppTriggerEvent::where('status', WhatsAppTriggerEvent::STATUS_SENT)
                ->whereDate('processed_at', today())
                ->count(),
            'failed' => WhatsAppTriggerEvent::where('status', WhatsAppTriggerEvent::STATUS_FAILED)->count(),
        ];

        return Inertia::render('Admin/Triggers/Index', [
            'events' => $events,
            'stats' => $stats,
        ]);
    }

    public function next(): Response
    {
        $event = WhatsAppTriggerEvent::query()
            ->with('user:id,name,email')
            ->whereIn('status', [
                WhatsAppTriggerEvent::STATUS_PENDING,
                WhatsAppTriggerEvent::STATUS_PROCESSING,
            ])
            ->orderByRaw("
                case
                    when status = 'pending' then 0
                    when status = 'processing' then 1
                    else 2
                end
            ")
            ->orderBy('id')
            ->first();

        return Inertia::render('Admin/Triggers/Next', [
            'event' => $event ? $this->transformEvent($event) : null,
        ]);
    }

    public function claim(Request $request, WhatsAppTriggerEvent $trigger): RedirectResponse
    {
        $claimed = false;

        DB::transaction(function () use ($request, $trigger, &$claimed): void {
            $lockedTrigger = WhatsAppTriggerEvent::query()
                ->whereKey($trigger->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedTrigger || $lockedTrigger->status !== WhatsAppTriggerEvent::STATUS_PENDING) {
                return;
            }

            $lockedTrigger->update([
                'status' => WhatsAppTriggerEvent::STATUS_PROCESSING,
                'claimed_at' => now(),
                'claimed_by' => $request->user()?->email ?? $request->user()?->name ?? 'admin-bot',
                'error_message' => null,
            ]);

            $claimed = true;
        });

        if (!$claimed) {
            return back()->with('error', 'This trigger is no longer pending.');
        }

        return back()->with('success', 'Trigger claimed.');
    }

    public function markSent(WhatsAppTriggerEvent $trigger): RedirectResponse
    {
        $trigger->update([
            'status' => WhatsAppTriggerEvent::STATUS_SENT,
            'processed_at' => now(),
            'error_message' => null,
        ]);

        return back()->with('success', 'Trigger marked as sent.');
    }

    public function markFailed(Request $request, WhatsAppTriggerEvent $trigger): RedirectResponse
    {
        $validated = $request->validate([
            'error_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $trigger->update([
            'status' => WhatsAppTriggerEvent::STATUS_FAILED,
            'processed_at' => now(),
            'attempts' => $trigger->attempts + 1,
            'error_message' => $validated['error_message'] ?? 'Failed during browser automation.',
        ]);

        return back()->with('success', 'Trigger marked as failed.');
    }

    private function transformEvent(WhatsAppTriggerEvent $event): array
    {
        return [
            'id' => $event->id,
            'event_key' => $event->event_key,
            'template_name' => $event->template_name,
            'user_name' => $event->user_name,
            'user_email' => $event->user?->email,
            'phone' => $event->phone,
            'message' => $event->message,
            'status' => $event->status,
            'attempts' => $event->attempts,
            'claimed_by' => $event->claimed_by,
            'error_message' => $event->error_message,
            'created_at' => $event->created_at?->toDateTimeString(),
            'processed_at' => $event->processed_at?->toDateTimeString(),
            'whatsapp_url' => 'https://web.whatsapp.com/send?phone=' . urlencode($event->phone) . '&text=' . urlencode($event->message),
            'can_claim' => $event->status === WhatsAppTriggerEvent::STATUS_PENDING,
            'can_complete' => in_array($event->status, [
                WhatsAppTriggerEvent::STATUS_PENDING,
                WhatsAppTriggerEvent::STATUS_PROCESSING,
            ], true),
        ];
    }
}

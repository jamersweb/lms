<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsApp\TriggerOutboxService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsAppSettingsController extends Controller
{
    public function index()
    {
        $templates = collect(config('triggers.templates', []))
            ->map(fn ($t, $key) => [
                'key' => $key,
                'template_name' => $t['template_name'],
                'message' => $t['message'],
            ])
            ->values()
            ->toArray();

        $users = User::orderBy('name')
            ->get(['id', 'name', 'email', 'whatsapp_number', 'whatsapp_opt_in'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'whatsapp_number' => $u->whatsapp_number,
                'has_whatsapp' => !empty($u->whatsapp_number) && $u->whatsapp_opt_in,
            ])
            ->toArray();

        return Inertia::render('Admin/WhatsAppSettings/Index', [
            'templates' => $templates,
            'users' => $users,
            'openclawBaseUrl' => rtrim(config('app.url'), '/') . '/api/openclaw',
        ]);
    }

    public function sendTest(Request $request, TriggerOutboxService $outboxService)
    {
        $validated = $request->validate([
            'template_key' => ['required', 'string'],
            'user_id' => ['required', 'exists:users,id'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        if (empty($user->whatsapp_number)) {
            return back()->with('error', 'Selected user has no WhatsApp number.');
        }

        $templates = config('triggers.templates', []);
        $template = $templates[$validated['template_key']] ?? null;
        if (!$template) {
            return back()->with('error', 'Invalid template.');
        }

        $event = $outboxService->queue(
            eventKey: $validated['template_key'],
            templateName: $template['template_name'],
            user: $user,
            message: $validated['message'],
            context: ['queued_from' => 'admin_test'],
        );

        if ($event) {
            return back()->with('success', 'Test trigger queued for ' . $user->whatsapp_number . '. OpenClaw can pick it up now.');
        }

        return back()->with('error', 'Trigger was not queued. Check that the user has WhatsApp opt-in and a phone number.');
    }
}

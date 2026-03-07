<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\WhatsApp\TriggerWebhookService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsAppSettingsController extends Controller
{
    public function index()
    {
        $webhookUrl = AppSetting::where('key', 'whatsapp_trigger_webhook_url')->first()?->value
            ?? config('whatsapp.trigger_webhook_url', '');

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
            'webhookUrl' => $webhookUrl,
            'templates' => $templates,
            'users' => $users,
        ]);
    }

    public function updateWebhook(Request $request)
    {
        $validated = $request->validate([
            'webhook_url' => ['required', 'string', 'url'],
        ]);

        AppSetting::updateOrCreate(
            ['key' => 'whatsapp_trigger_webhook_url'],
            ['value' => $validated['webhook_url']]
        );

        return back()->with('success', 'Webhook URL updated.');
    }

    public function sendTest(Request $request)
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

        $webhookUrl = AppSetting::where('key', 'whatsapp_trigger_webhook_url')->first()?->value
            ?? config('whatsapp.trigger_webhook_url');

        if (empty($webhookUrl)) {
            return back()->with('error', 'Webhook URL is not configured.');
        }

        $service = new TriggerWebhookService($webhookUrl);
        $ok = $service->send(
            $template['template_name'],
            $validated['message'],
            $user->whatsapp_number
        );

        if ($ok) {
            return back()->with('success', 'Message sent successfully to ' . $user->whatsapp_number);
        }

        return back()->with('error', 'Failed to send. Check storage/logs/laravel.log for details.');
    }
}

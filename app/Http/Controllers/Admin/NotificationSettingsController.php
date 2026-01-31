<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AppSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationSettingsController extends Controller
{
    public function index()
    {
        $settings = app(AppSettings::class)->getNotificationSettings();

        return Inertia::render('Admin/Notifications/Settings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'channels.email' => 'boolean',
            'channels.whatsapp' => 'boolean',
            'drip.enabled' => 'boolean',
            'drip.send_hour' => 'integer|min:0|max:23',
            'task.enabled' => 'boolean',
            'task.send_hour' => 'integer|min:0|max:23',
            'stagnation.enabled' => 'boolean',
            'stagnation.inactive_days' => 'integer|min:1|max:30',
            'stagnation.send_hour' => 'integer|min:0|max:23',
        ]);

        app(AppSettings::class)->setNotificationSettings($validated);

        return redirect()->back()->with('success', 'Notification settings updated successfully.');
    }
}

<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing application-wide settings stored in database.
 * Settings override config defaults and can be updated via admin UI.
 */
class AppSettings
{
    /**
     * Get a setting value, with optional default.
     *
     * Checks cache first, then database, then falls back to default.
     */
    public function get(string $key, $default = null)
    {
        return Cache::remember("app_setting.{$key}", 3600, function () use ($key, $default) {
            $setting = AppSetting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, $value): void
    {
        AppSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Clear cache
        Cache::forget("app_setting.{$key}");
    }

    /**
     * Get notification settings with config fallback.
     */
    public function getNotificationSettings(): array
    {
        $enabled = $this->get('notifications.enabled');
        $enabled = $enabled === null ? config('notifications_lms.enabled', true) : (bool) $enabled;

        return [
            'enabled' => $enabled,
            'channels' => [
                'email' => (bool) ($this->get('notifications.channels.email') ?? config('notifications_lms.channels.email', true)),
                'whatsapp' => (bool) ($this->get('notifications.channels.whatsapp') ?? config('notifications_lms.channels.whatsapp', true)),
            ],
            'drip' => [
                'enabled' => (bool) ($this->get('notifications.drip.enabled') ?? config('notifications_lms.drip.enabled', true)),
                'send_hour' => (int) ($this->get('notifications.drip.send_hour') ?? config('notifications_lms.drip.send_hour', 9)),
            ],
            'task' => [
                'enabled' => (bool) ($this->get('notifications.task.enabled') ?? config('notifications_lms.task.enabled', true)),
                'send_hour' => (int) ($this->get('notifications.task.send_hour') ?? config('notifications_lms.task.send_hour', 19)),
            ],
            'stagnation' => [
                'enabled' => (bool) ($this->get('notifications.stagnation.enabled') ?? config('notifications_lms.stagnation.enabled', true)),
                'inactive_days' => (int) ($this->get('notifications.stagnation.inactive_days') ?? config('notifications_lms.stagnation.inactive_days', 3)),
                'send_hour' => (int) ($this->get('notifications.stagnation.send_hour') ?? config('notifications_lms.stagnation.send_hour', 10)),
            ],
        ];
    }

    /**
     * Set notification settings.
     */
    public function setNotificationSettings(array $settings): void
    {
        // Set top-level enabled
        if (isset($settings['enabled'])) {
            $this->set('notifications.enabled', $settings['enabled']);
        }

        // Set channels
        if (isset($settings['channels'])) {
            foreach ($settings['channels'] as $channel => $enabled) {
                $this->set("notifications.channels.{$channel}", $enabled);
            }
        }

        // Set drip settings
        if (isset($settings['drip'])) {
            foreach ($settings['drip'] as $key => $value) {
                $this->set("notifications.drip.{$key}", $value);
            }
        }

        // Set task settings
        if (isset($settings['task'])) {
            foreach ($settings['task'] as $key => $value) {
                $this->set("notifications.task.{$key}", $value);
            }
        }

        // Set stagnation settings
        if (isset($settings['stagnation'])) {
            foreach ($settings['stagnation'] as $key => $value) {
                $this->set("notifications.stagnation.{$key}", $value);
            }
        }
    }
}

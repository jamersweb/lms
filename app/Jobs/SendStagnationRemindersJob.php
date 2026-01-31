<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\StagnationReminderNotification;
use App\Services\AppSettings;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendStagnationRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $settings = app(AppSettings::class)->getNotificationSettings();

        // Check if stagnation reminders are enabled
        if (!$settings['enabled'] || !$settings['stagnation']['enabled']) {
            return;
        }

        // Check if current hour matches send_hour
        $currentHour = now()->hour;
        if ($currentHour != $settings['stagnation']['send_hour']) {
            return;
        }

        $inactiveDays = $settings['stagnation']['inactive_days'];
        $thresholdDate = Carbon::now()->subDays($inactiveDays);

        // Find users who:
        // 1. last_active_at is older than threshold
        // 2. Are enrolled in at least one course
        // 3. Have opted into notifications
        // 4. Haven't been reminded in last N days (to avoid spam)

        $users = User::where(function ($query) use ($thresholdDate) {
            $query->whereNull('last_active_at')
                ->orWhere('last_active_at', '<', $thresholdDate);
        })
        ->whereHas('enrollments')
        ->where(function ($query) {
            $query->where('email_reminders_opt_in', true)
                ->orWhere(function ($q) {
                    $q->where('whatsapp_opt_in', true)
                        ->whereNotNull('whatsapp_number');
                });
        })
        ->get();

        $today = Carbon::today();

        foreach ($users as $user) {
            // Check if already reminded recently (within last inactive_days)
            $lastReminder = NotificationLog::where('user_id', $user->id)
                ->where('type', 'stagnation')
                ->where('sent_on', '>=', $thresholdDate->toDateString())
                ->latest('sent_on')
                ->first();

            if ($lastReminder) {
                continue; // Already reminded recently
            }

            // Calculate actual inactive days
            $lastActive = $user->last_active_at ? Carbon::parse($user->last_active_at) : null;
            $actualInactiveDays = $lastActive ? $lastActive->diffInDays(now()) : $inactiveDays;

            // Send notification
            $user->notify(new StagnationReminderNotification($actualInactiveDays));

            // Log notification (use updateOrCreate to handle race conditions)
            try {
                NotificationLog::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => 'stagnation',
                        'sent_on' => $today->toDateString(),
                    ],
                    [
                        'meta' => [
                            'inactive_days' => $actualInactiveDays,
                        ],
                    ]
                );
            } catch (\Exception $e) {
                // Ignore duplicate entry errors
            }
        }
    }
}

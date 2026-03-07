<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Runs all scheduled WhatsApp triggers based on current time.
 * Call via HTTP (e.g. cron-job.org) or dispatch hourly - no server cron needed.
 */
class ProcessScheduledTriggersJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $now = now();
        $hour = (int) $now->format('G');
        $dayOfWeek = (int) $now->format('w'); // 0=Sun, 5=Fri
        $dayOfMonth = (int) $now->format('j');

        $results = [];

        // Habit entry reminder – daily 8 PM (20:00)
        if ($hour === 20) {
            Artisan::call('lms:trigger-habit-entry-reminder');
            $results['habit_entry_reminder'] = trim(Artisan::output());
        }

        // Investment reminder – 1st of month, 9 AM
        if ($dayOfMonth === 1 && $hour === 9) {
            Artisan::call('lms:trigger-investment-reminder');
            $results['investment_reminder'] = trim(Artisan::output());
        }

        // Friday Jumuah – Fridays 8 AM
        if ($dayOfWeek === 5 && $hour === 8) {
            Artisan::call('lms:trigger-friday-jummah');
            $results['friday_jummah'] = trim(Artisan::output());
        }

        // Maintenance habit – Mondays 9 AM
        if ($dayOfWeek === 1 && $hour === 9) {
            Artisan::call('lms:trigger-maintenance-habit');
            $results['maintenance_habit'] = trim(Artisan::output());
        }

        // Intent renewal – Mondays 10 AM
        if ($dayOfWeek === 1 && $hour === 10) {
            Artisan::call('lms:trigger-intent-renewal');
            $results['intent_renewal'] = trim(Artisan::output());
        }

        // We miss you – daily 9 AM
        if ($hour === 9) {
            Artisan::call('lms:trigger-we-miss-you', ['--days' => 7]);
            $results['we_miss_you'] = trim(Artisan::output());
        }

        if (! empty($results)) {
            Log::info('ProcessScheduledTriggersJob completed', array_keys($results));
        }
    }
}

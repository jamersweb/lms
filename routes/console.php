<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lms:drip-unlock')->dailyAt('02:00');
Schedule::command('lms:stagnation-check --days=3')->dailyAt('02:30');
Schedule::command('lms:stagnation-scan --days=3')->dailyAt('03:00');
Schedule::command('lms:send-nudges')->everyMinute(); // Check every minute for scheduled nudges

// Phase 4 Task 1: Notification reminder jobs
// These jobs check their own send_hour settings internally
Schedule::job(\App\Jobs\SendDripRemindersJob::class)->hourly();
Schedule::job(\App\Jobs\SendTaskRemindersJob::class)->hourly();
Schedule::job(\App\Jobs\SendStagnationRemindersJob::class)->hourly();

// Phase 4 Task 2: Micro nudge job (runs frequently to check campaign schedules)
Schedule::job(\App\Jobs\SendMicroNudgeJob::class)->everyFiveMinutes();

Schedule::call(function () {
    $lock = \Illuminate\Support\Facades\Cache::lock('scheduled-triggers', 3600);
    if ($lock->get()) {
        try { (new \App\Jobs\ProcessScheduledTriggersJob)->handle(); }
        finally { $lock->release(); }
    }
})->name('lms-triggers')->hourly()->withoutOverlapping();
Schedule::job(\App\Jobs\AggregateDailyUserMetricsJob::class)->dailyAt('01:00')->withoutOverlapping();
Schedule::job(\App\Jobs\UpdateCourseProgressSnapshotsJob::class)->hourly()->withoutOverlapping();
Schedule::command('lms:backup')->dailyAt('01:30')->withoutOverlapping()->when(fn () => config('backup.enabled'));

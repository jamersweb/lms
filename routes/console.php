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

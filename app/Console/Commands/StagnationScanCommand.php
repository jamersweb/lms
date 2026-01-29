<?php

namespace App\Console\Commands;

use App\Models\StagnationAlert;
use App\Models\User;
use App\Notifications\StagnationReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class StagnationScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:stagnation-scan {--days=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan for stalled users and record stagnation alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days') ?: 3;
        $cutoff = now()->subDays($days);

        $this->info("Scanning for users stalled for >= {$days} days (cutoff {$cutoff})");

        $users = User::whereHas('lessonProgress', function ($q) {
                $q->whereNotNull('last_heartbeat_at')
                  ->orWhereNotNull('completed_at');
            })
            ->with('lessonProgress')
            ->get();

        $alerted = 0;

        foreach ($users as $user) {
            $lastHeartbeat = $user->lessonProgress->max('last_heartbeat_at');
            $lastCompletion = $user->lessonProgress->max('completed_at');

            $timestamps = collect([$lastHeartbeat, $lastCompletion])
                ->filter()
                ->map(fn ($v) => $v instanceof \DateTimeInterface ? $v : \Illuminate\Support\Carbon::parse($v));

            if ($timestamps->isEmpty()) {
                continue;
            }

            $lastActivity = $timestamps->max();

            if ($lastActivity->gt($cutoff)) {
                continue;
            }

            StagnationAlert::updateOrCreate(
                ['user_id' => $user->id, 'days' => $days],
                ['last_activity_at' => $lastActivity]
            );

            Notification::send($user, new StagnationReminderNotification($days));

            $alerted++;
        }

        $this->info("Stagnation scan complete. Alerts recorded for {$alerted} users.");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\LessonProgress;
use App\Models\User;
use App\Models\NotificationPreference;
use App\Notifications\StagnationReminderNotification;
use App\Services\WhatsApp\FakeWhatsAppChannel;
use App\Services\WhatsApp\WhatsAppChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class StagnationCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:stagnation-check {--days=3}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders to users with no lesson activity for N days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days') ?: 3;
        $cutoff = now()->subDays($days);

        $this->info("Checking for users stalled for >= {$days} days (cutoff {$cutoff})");

        $users = User::whereHas('enrollments')
            ->with(['enrollments', 'lessonProgress', 'notificationPreference'])
            ->get();

        $whatsAppDriver = config('whatsapp.driver', 'fake');
        $whatsAppChannel = $whatsAppDriver === 'fake'
            ? new FakeWhatsAppChannel()
            : null; // placeholder for future real drivers

        $notifiedCount = 0;

        foreach ($users as $user) {
            $lastHeartbeat = $user->lessonProgress->max('last_heartbeat_at');
            $lastCompletion = $user->lessonProgress->max('completed_at');
            $lastEnrollment = $user->enrollments->max('enrolled_at');

            $timestamps = collect([$lastHeartbeat, $lastCompletion, $lastEnrollment])
                ->filter()
                ->map(fn ($value) => $value instanceof \DateTimeInterface ? $value : \Illuminate\Support\Carbon::parse($value));

            if ($timestamps->isEmpty()) {
                continue;
            }

            $lastActivity = $timestamps->max();

            if ($lastActivity->gt($cutoff)) {
                continue;
            }

            /** @var NotificationPreference|null $prefs */
            $prefs = $user->notificationPreference;

            // Email notification
            if (! $prefs || $prefs->email_enabled) {
                Notification::send($user, new StagnationReminderNotification($days));
                $notifiedCount++;
            }

            // WhatsApp notification
            if ($whatsAppChannel && $prefs && $prefs->whatsapp_enabled) {
                $message = "Assalamu alaikum {$user->name}, we haven’t seen progress on your lessons in {$days} days. "
                    ."Take a few minutes today to continue your Sunnah learning in shaa Allah.";
                $whatsAppChannel->send('+10000000000', $message);
            }
        }

        $this->info("Stagnation check complete. Email reminders sent to {$notifiedCount} users.");

        return Command::SUCCESS;
    }
}

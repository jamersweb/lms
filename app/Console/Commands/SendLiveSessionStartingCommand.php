<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\WhatsApp\TriggerService;
use Illuminate\Console\Command;

class SendLiveSessionStartingCommand extends Command
{
    protected $signature = 'lms:trigger-live-session {link : The live session URL to include} {--course= : Optional course ID to target enrolled users only}';
    protected $description = 'Send live session starting reminder (run manually or via cron before a live session)';

    public function handle(TriggerService $triggerService): int
    {
        $link = $this->argument('link');
        $courseId = $this->option('course');

        $users = $courseId
            ? User::whereHas('enrollments', fn ($q) => $q->where('course_id', $courseId))
                ->where('whatsapp_opt_in', true)
                ->whereNotNull('whatsapp_number')
                ->get()
            : User::whereHas('enrollments')
                ->where('whatsapp_opt_in', true)
                ->whereNotNull('whatsapp_number')
                ->get();

        $count = $triggerService->fireBulk('live_session_starting', $users, ['link' => $link]);

        $this->info("Sent live session reminder to {$count} users.");

        return Command::SUCCESS;
    }
}

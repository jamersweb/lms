<?php

namespace App\Console\Commands;

use App\Models\HabitLog;
use App\Models\User;
use App\Services\WhatsApp\TriggerService;
use Illuminate\Console\Command;

class SendMaintenanceHabitCommand extends Command
{
    protected $signature = 'lms:trigger-maintenance-habit';
    protected $description = 'Send maintenance habit nudge to users who have logged habits (weekly)';

    public function handle(TriggerService $triggerService): int
    {
        $userIdsWithHabits = HabitLog::where('log_date', '>=', now()->subDays(30))
            ->pluck('user_id')
            ->unique();

        $users = User::whereIn('id', $userIdsWithHabits)
            ->where('whatsapp_opt_in', true)
            ->whereNotNull('whatsapp_number')
            ->get();

        $count = $triggerService->fireBulk('maintenance_habit', $users);

        $this->info("Sent maintenance habit nudge to {$count} users.");

        return Command::SUCCESS;
    }
}

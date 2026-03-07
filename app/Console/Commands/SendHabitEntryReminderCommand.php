<?php

namespace App\Console\Commands;

use App\Models\HabitLog;
use App\Models\User;
use App\Services\WhatsApp\TriggerService;
use Illuminate\Console\Command;

class SendHabitEntryReminderCommand extends Command
{
    protected $signature = 'lms:trigger-habit-entry-reminder';
    protected $description = 'Send habit entry reminder to users who have logged habits before and haven\'t logged today (evening)';

    public function handle(TriggerService $triggerService): int
    {
        $userIdsWithRecentHabitLogs = HabitLog::where('log_date', '>=', now()->subDays(30))
            ->pluck('user_id')
            ->unique();

        $loggedTodayUserIds = HabitLog::whereDate('log_date', today())
            ->pluck('user_id')
            ->unique();

        $usersToRemind = User::whereIn('id', $userIdsWithRecentHabitLogs)
            ->whereNotIn('id', $loggedTodayUserIds)
            ->where('whatsapp_opt_in', true)
            ->whereNotNull('whatsapp_number')
            ->get();

        $count = $triggerService->fireBulk('habit_entry_reminder', $usersToRemind);

        $this->info("Sent habit entry reminder to {$count} users.");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\DailyUserMetric;
use App\Models\User;
use App\Services\WhatsApp\TriggerService;
use Illuminate\Console\Command;

class SendInvestmentReminderCommand extends Command
{
    protected $signature = 'lms:trigger-investment-reminder';
    protected $description = 'Send investment reminder to users who have watched 10+ hours this month (monthly)';

    public function handle(TriggerService $triggerService): int
    {
        $startOfMonth = now()->startOfMonth();

        $usersWithHours = DailyUserMetric::where('date', '>=', $startOfMonth)
            ->select('user_id')
            ->selectRaw('SUM(watched_seconds) as total_seconds')
            ->groupBy('user_id')
            ->havingRaw('SUM(watched_seconds) >= 36000') // 10 hours
            ->pluck('total_seconds', 'user_id');

        $count = 0;
        foreach ($usersWithHours as $userId => $totalSeconds) {
            $user = User::where('id', $userId)
                ->where('whatsapp_opt_in', true)
                ->whereNotNull('whatsapp_number')
                ->first();

            if (!$user) {
                continue;
            }

            $hours = (int) floor($totalSeconds / 3600);
            $triggerService->fire('investment_reminder', $user, ['hours' => (string) $hours]);
            $count++;
        }

        $this->info("Sent investment reminder to {$count} users.");

        return Command::SUCCESS;
    }
}

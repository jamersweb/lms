<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WhatsApp\TriggerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendWeMissYouCommand extends Command
{
    protected $signature = 'lms:trigger-we-miss-you {--days=7 : Inactive days threshold}';
    protected $description = 'Send we miss you nudge to stagnant users (no activity for N days)';

    public function handle(TriggerService $triggerService): int
    {
        $days = (int) $this->option('days');
        $threshold = Carbon::now()->subDays($days);

        $users = User::where(function ($query) use ($threshold) {
            $query->whereNull('last_active_at')
                ->orWhere('last_active_at', '<', $threshold);
        })
            ->whereHas('enrollments')
            ->where('whatsapp_opt_in', true)
            ->whereNotNull('whatsapp_number')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $cacheKey = "trigger_sent:we_miss_you:{$user->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }
            $triggerService->fire('we_miss_you', $user);
            Cache::put($cacheKey, true, now()->addDays(7)); // Don't send again for 7 days
            $count++;
        }

        $this->info("Sent we miss you to {$count} stagnant users (inactive {$days}+ days).");

        return Command::SUCCESS;
    }
}

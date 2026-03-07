<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\WhatsApp\TriggerService;
use Illuminate\Console\Command;

class SendIntentRenewalCommand extends Command
{
    protected $signature = 'lms:trigger-intent-renewal';
    protected $description = 'Send intent renewal nudge to enrolled users (weekly)';

    public function handle(TriggerService $triggerService): int
    {
        $userIds = Enrollment::distinct()->pluck('user_id');

        $users = User::whereIn('id', $userIds)
            ->where('whatsapp_opt_in', true)
            ->whereNotNull('whatsapp_number')
            ->get();

        $count = $triggerService->fireBulk('intent_renewal', $users);

        $this->info("Sent intent renewal to {$count} users.");

        return Command::SUCCESS;
    }
}

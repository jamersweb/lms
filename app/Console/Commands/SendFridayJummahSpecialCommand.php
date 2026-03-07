<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\WhatsApp\TriggerService;
use Illuminate\Console\Command;

class SendFridayJummahSpecialCommand extends Command
{
    protected $signature = 'lms:trigger-friday-jummah';
    protected $description = 'Send Friday Jumuah reminder to opted-in users (Fridays only)';

    public function handle(TriggerService $triggerService): int
    {
        if (!now()->isFriday()) {
            $this->info('Not Friday, skipping.');
            return Command::SUCCESS;
        }

        $users = User::where('whatsapp_opt_in', true)
            ->whereNotNull('whatsapp_number')
            ->get();

        $count = $triggerService->fireBulk('friday_jummah_special', $users);

        $this->info("Sent Friday Jumuah reminder to {$count} users.");

        return Command::SUCCESS;
    }
}

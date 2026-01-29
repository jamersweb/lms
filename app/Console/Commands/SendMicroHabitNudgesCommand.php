<?php

namespace App\Console\Commands;

use App\Models\MicroHabitNudge;
use App\Models\User;
use App\Models\NudgeDelivery;
use App\Services\WhatsApp\WhatsAppChannel;
use App\Services\WhatsApp\FakeWhatsAppChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class SendMicroHabitNudgesCommand extends Command
{
    protected $signature = 'lms:send-nudges';
    protected $description = 'Send micro-habit nudges via WhatsApp at scheduled times';

    public function handle()
    {
        $now = now();
        $currentTime = $now->format('H:i');
        $currentDayOfWeek = $now->dayOfWeek; // 0=Sunday, 6=Saturday

        $nudges = MicroHabitNudge::where('is_active', true)
            ->where('send_at', $currentTime)
            ->get();

        if ($nudges->isEmpty()) {
            $this->info('No nudges scheduled for this time.');
            return Command::SUCCESS;
        }

        $whatsAppDriver = config('whatsapp.driver', 'fake');
        $whatsAppChannel = $whatsAppDriver === 'fake'
            ? new FakeWhatsAppChannel()
            : null; // placeholder for future real drivers

        if (!$whatsAppChannel) {
            $this->error('WhatsApp channel not configured.');
            return Command::FAILURE;
        }

        $users = User::whereHas('notificationPreference', function ($q) {
                $q->where('whatsapp_enabled', true);
            })
            ->get();

        $sentCount = 0;

        foreach ($nudges as $nudge) {
            // Check if nudge should be sent today
            if ($nudge->target_days && !in_array($currentDayOfWeek, $nudge->target_days)) {
                continue;
            }

            foreach ($users as $user) {
                // Check if already sent today
                $alreadySent = NudgeDelivery::where('user_id', $user->id)
                    ->where('micro_habit_nudge_id', $nudge->id)
                    ->whereDate('sent_at', $now->toDateString())
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                // Send WhatsApp message with audio
                $message = "Assalamu alaikum {$user->name}! 🌙\n\n";
                $message .= "{$nudge->title}\n\n";
                $message .= "Listen to this 30-second reminder: {$nudge->audio_url}";

                try {
                    $phoneNumber = $user->phone ?? '+10000000000'; // Default for testing
                    $whatsAppChannel->send($phoneNumber, $message);

                    NudgeDelivery::create([
                        'user_id' => $user->id,
                        'micro_habit_nudge_id' => $nudge->id,
                        'sent_at' => $now,
                        'delivery_status' => 'sent',
                    ]);

                    $sentCount++;
                } catch (\Exception $e) {
                    NudgeDelivery::create([
                        'user_id' => $user->id,
                        'micro_habit_nudge_id' => $nudge->id,
                        'sent_at' => $now,
                        'delivery_status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Sent {$sentCount} micro-habit nudges.");

        return Command::SUCCESS;
    }
}

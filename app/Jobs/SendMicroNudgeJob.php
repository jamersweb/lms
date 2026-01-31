<?php

namespace App\Jobs;

use App\Models\AudioClip;
use App\Models\MicroNudgeCampaign;
use App\Models\MicroNudgeDelivery;
use App\Models\User;
use App\Services\AudienceFilterService;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendMicroNudgeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaigns = MicroNudgeCampaign::where('is_enabled', true)->get();
        $audienceFilterService = app(AudienceFilterService::class);
        $provider = $this->resolveProvider();

        foreach ($campaigns as $campaign) {
            if (!$this->isDue($campaign)) {
                continue; // Not due yet
            }

            // Get target users
            $users = User::where('whatsapp_opt_in', true)
                ->whereNotNull('whatsapp_number')
                ->get()
                ->filter(function ($user) use ($campaign, $audienceFilterService) {
                    return $audienceFilterService->matches($user, $campaign->audience_filters);
                });

            if ($users->isEmpty()) {
                continue; // No matching users
            }

            // Get next clip
            $clip = $campaign->getNextClip();
            if (!$clip) {
                Log::warning('Micro nudge campaign has no clips', ['campaign_id' => $campaign->id]);
                continue;
            }

            foreach ($users as $user) {
                // Check if already delivered in this window
                $userDedupeKey = $this->generateDedupeKey($campaign, $user);
                $existingDelivery = MicroNudgeDelivery::where('dedupe_key', $userDedupeKey)->first();

                if ($existingDelivery) {
                    continue; // Already sent in this window
                }

                // Create delivery record
                $delivery = MicroNudgeDelivery::create([
                    'campaign_id' => $campaign->id,
                    'audio_clip_id' => $clip->id,
                    'user_id' => $user->id,
                    'channel' => 'whatsapp',
                    'status' => MicroNudgeDelivery::STATUS_QUEUED,
                    'dedupe_key' => $userDedupeKey,
                ]);

                try {
                    // Send via WhatsApp
                    $clipUrl = $clip->playable_url;
                    $caption = "Sunnah of the Hour: {$clip->title}";

                    if ($provider->supportsAudio() && $clipUrl) {
                        $provider->sendAudio($user->whatsapp_number, $clipUrl, $caption);
                    } else {
                        // Fallback to text message with link
                        $message = "{$caption}\n\nListen: {$clipUrl}";
                        $provider->sendMessage($user->whatsapp_number, $message);
                    }

                    // Update delivery as sent
                    $delivery->update([
                        'status' => MicroNudgeDelivery::STATUS_SENT,
                        'sent_at' => now(),
                    ]);

                } catch (\Exception $e) {
                    // Mark as failed
                    $delivery->update([
                        'status' => MicroNudgeDelivery::STATUS_FAILED,
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Micro nudge delivery failed', [
                        'delivery_id' => $delivery->id,
                        'user_id' => $user->id,
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update campaign's last_sent_clip_id for sequence rotation
            if ($campaign->rotation === MicroNudgeCampaign::ROTATION_SEQUENCE) {
                $campaign->update(['last_sent_clip_id' => $clip->id]);
            }
        }
    }

    /**
     * Check if a campaign is due to run now.
     */
    private function isDue(MicroNudgeCampaign $campaign): bool
    {
        $now = now();

        if ($campaign->timezone) {
            $now = $now->setTimezone($campaign->timezone);
        }

        if ($campaign->schedule_type === MicroNudgeCampaign::SCHEDULE_HOURLY) {
            // Due every hour at the specified minute
            return $now->minute === $campaign->send_minute;
        }

        if ($campaign->schedule_type === MicroNudgeCampaign::SCHEDULE_DAILY) {
            // Due at the specified hour:minute each day
            return $now->hour === $campaign->send_hour
                && $now->minute === $campaign->send_minute;
        }

        return false;
    }

    /**
     * Generate dedupe key for a user and campaign.
     */
    private function generateDedupeKey(MicroNudgeCampaign $campaign, User $user): string
    {
        $datePart = match ($campaign->schedule_type) {
            MicroNudgeCampaign::SCHEDULE_HOURLY => now()->format('Y-m-d-H'),
            MicroNudgeCampaign::SCHEDULE_DAILY => now()->format('Y-m-d'),
            default => now()->format('Y-m-d-H'),
        };

        return sha1("{$campaign->id}-{$user->id}-{$datePart}");
    }

    /**
     * Resolve the WhatsApp provider.
     */
    private function resolveProvider(): WhatsAppProviderInterface
    {
        // Check if a provider is already bound in the container (for testing)
        if (app()->bound(WhatsAppProviderInterface::class)) {
            return app(WhatsAppProviderInterface::class);
        }

        $providerClass = match (config('whatsapp.provider', 'log')) {
            'log' => \App\Services\WhatsApp\LogWhatsAppProvider::class,
            // 'twilio' => \App\Services\WhatsApp\TwilioWhatsAppProvider::class,
            default => \App\Services\WhatsApp\LogWhatsAppProvider::class,
        };

        return app($providerClass);
    }
}

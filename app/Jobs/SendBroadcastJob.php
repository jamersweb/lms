<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Models\BroadcastDelivery;
use App\Notifications\BroadcastEmailNotification;
use App\Notifications\BroadcastInAppNotification;
use App\Services\BroadcastAudienceService;
use App\Services\WhatsApp\WhatsAppProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendBroadcastJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $broadcastId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $broadcast = Broadcast::findOrFail($this->broadcastId);

        if ($broadcast->status === Broadcast::STATUS_SENT) {
            Log::warning('Broadcast already sent', ['broadcast_id' => $this->broadcastId]);
            return;
        }

        // Update status to sending
        $broadcast->update(['status' => Broadcast::STATUS_SENDING]);

        $audienceService = app(BroadcastAudienceService::class);
        $whatsappProvider = $this->resolveWhatsAppProvider();

        $filters = $broadcast->audience_filters ?? [];
        $channels = $broadcast->channels ?? [];

        // Process users in chunks
        $audienceService->chunkedUsers($filters, 500, function ($users) use ($broadcast, $channels, $audienceService, $whatsappProvider) {
            foreach ($users as $user) {
                foreach ($channels as $channel) {
                    // Generate dedupe key first
                    $dedupeKey = $this->generateDedupeKey($broadcast->id, $user->id, $channel);

                    // Check if already delivered
                    if (BroadcastDelivery::where('dedupe_key', $dedupeKey)->exists()) {
                        continue; // Skip duplicate
                    }

                    // Check channel-specific opt-ins
                    $meetsRequirements = true;
                    if ($channel === Broadcast::CHANNEL_EMAIL) {
                        $meetsRequirements = $user->email_reminders_opt_in === true;
                    } elseif ($channel === Broadcast::CHANNEL_WHATSAPP) {
                        $meetsRequirements = $user->whatsapp_opt_in === true && !empty($user->whatsapp_number);
                    }
                    // in_app: always allowed

                    if (!$meetsRequirements) {
                        // User doesn't meet channel requirements
                        $this->createDelivery($broadcast, $user, $channel, BroadcastDelivery::STATUS_SKIPPED, 'User opt-out or missing requirements');
                        continue;
                    }

                    // Create delivery record
                    $delivery = $this->createDelivery($broadcast, $user, $channel, BroadcastDelivery::STATUS_QUEUED);

                    try {
                        // Send via appropriate channel
                        if ($channel === Broadcast::CHANNEL_EMAIL) {
                            $user->notify(new BroadcastEmailNotification($broadcast));
                            $delivery->update([
                                'status' => BroadcastDelivery::STATUS_SENT,
                                'sent_at' => now(),
                            ]);
                        } elseif ($channel === Broadcast::CHANNEL_WHATSAPP) {
                            $message = $this->formatWhatsAppMessage($broadcast);
                            $whatsappProvider->sendMessage($user->whatsapp_number, $message);
                            $delivery->update([
                                'status' => BroadcastDelivery::STATUS_SENT,
                                'sent_at' => now(),
                            ]);
                        } elseif ($channel === Broadcast::CHANNEL_IN_APP) {
                            $user->notify(new BroadcastInAppNotification($broadcast));
                            $delivery->update([
                                'status' => BroadcastDelivery::STATUS_SENT,
                                'sent_at' => now(),
                            ]);
                        }
                    } catch (\Exception $e) {
                        $delivery->update([
                            'status' => BroadcastDelivery::STATUS_FAILED,
                            'error' => $e->getMessage(),
                        ]);

                        Log::error('Broadcast delivery failed', [
                            'broadcast_id' => $broadcast->id,
                            'user_id' => $user->id,
                            'channel' => $channel,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        });

        // Mark broadcast as sent
        $broadcast->update([
            'status' => Broadcast::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Create a delivery record.
     */
    private function createDelivery(Broadcast $broadcast, $user, string $channel, string $status, ?string $error = null): BroadcastDelivery
    {
        $dedupeKey = $this->generateDedupeKey($broadcast->id, $user->id, $channel);

        return BroadcastDelivery::create([
            'broadcast_id' => $broadcast->id,
            'user_id' => $user->id,
            'channel' => $channel,
            'status' => $status,
            'error' => $error,
            'dedupe_key' => $dedupeKey,
            'sent_at' => $status === BroadcastDelivery::STATUS_SENT ? now() : null,
        ]);
    }

    /**
     * Generate dedupe key for a broadcast delivery.
     */
    private function generateDedupeKey(int $broadcastId, int $userId, string $channel): string
    {
        return sha1("broadcast:{$broadcastId}:user:{$userId}:channel:{$channel}");
    }

    /**
     * Format message for WhatsApp (with truncation).
     */
    private function formatWhatsAppMessage(Broadcast $broadcast): string
    {
        $maxLength = 800;
        $title = "*{$broadcast->title}*";
        $body = $broadcast->body;

        // Truncate if needed
        if (mb_strlen($body) > $maxLength) {
            $body = mb_substr($body, 0, $maxLength - 50) . '...';
            $body .= "\n\nOpen app to read full message";
        }

        $message = "{$title}\n\n{$body}";

        // Add app link if available
        $appUrl = config('app.url');
        if ($appUrl) {
            $message .= "\n\nView in app: {$appUrl}/inbox/{$broadcast->id}";
        }

        return $message;
    }

    /**
     * Resolve WhatsApp provider.
     */
    private function resolveWhatsAppProvider(): WhatsAppProviderInterface
    {
        // Check if a provider is already bound (for testing)
        if (app()->bound(WhatsAppProviderInterface::class)) {
            return app(WhatsAppProviderInterface::class);
        }

        $providerClass = match (config('whatsapp.provider', 'log')) {
            'log' => \App\Services\WhatsApp\LogWhatsAppProvider::class,
            default => \App\Services\WhatsApp\LogWhatsAppProvider::class,
        };

        return app($providerClass);
    }
}

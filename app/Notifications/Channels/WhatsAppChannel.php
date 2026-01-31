<?php

namespace App\Notifications\Channels;

use App\Services\WhatsApp\WhatsAppProviderInterface;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Custom notification channel for WhatsApp.
 *
 * Usage in Notification class:
 * public function via($notifiable) {
 *     return ['whatsapp'];
 * }
 */
class WhatsAppChannel
{
    protected WhatsAppProviderInterface $provider;

    public function __construct()
    {
        $providerClass = $this->resolveProvider();
        $this->provider = app($providerClass);
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        // Check if notification has audio support
        if (method_exists($notification, 'toWhatsAppAudio')) {
            $audioData = $notification->toWhatsAppAudio($notifiable);

            if ($audioData && !empty($audioData['url'])) {
                $whatsappNumber = $notifiable->whatsapp_number ?? null;

                if (empty($whatsappNumber)) {
                    Log::warning('WhatsApp audio notification skipped: no phone number', [
                        'user_id' => $notifiable->id,
                    ]);
                    return;
                }

                try {
                    $caption = $audioData['caption'] ?? '';
                    if ($this->provider->supportsAudio()) {
                        $this->provider->sendAudio($whatsappNumber, $audioData['url'], $caption);
                    } else {
                        // Fallback to text message with link
                        $message = $caption ? "{$caption}\n\nListen: {$audioData['url']}" : "Listen: {$audioData['url']}";
                        $this->provider->sendMessage($whatsappNumber, $message);
                    }
                    return;
                } catch (\Exception $e) {
                    Log::error('WhatsApp audio notification failed', [
                        'user_id' => $notifiable->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        }

        // Fallback to text message
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (empty($message)) {
            return;
        }

        // Get WhatsApp number from notifiable
        $whatsappNumber = $notifiable->whatsapp_number ?? null;

        if (empty($whatsappNumber)) {
            Log::warning('WhatsApp notification skipped: no phone number', [
                'user_id' => $notifiable->id,
            ]);
            return;
        }

        try {
            $this->provider->sendMessage($whatsappNumber, $message);
        } catch (\Exception $e) {
            Log::error('WhatsApp notification failed', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resolve the WhatsApp provider class based on config.
     */
    protected function resolveProvider(): string
    {
        $provider = config('whatsapp.provider', 'log');

        return match ($provider) {
            'log' => \App\Services\WhatsApp\LogWhatsAppProvider::class,
            // 'twilio' => \App\Services\WhatsApp\TwilioWhatsAppProvider::class,
            default => \App\Services\WhatsApp\LogWhatsAppProvider::class,
        };
    }
}

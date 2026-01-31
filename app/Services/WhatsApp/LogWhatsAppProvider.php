<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

/**
 * Stub WhatsApp provider that logs messages instead of sending them.
 * Used for development/testing when WHATSAPP_PROVIDER=log.
 */
class LogWhatsAppProvider implements WhatsAppProviderInterface
{
    public function sendMessage(string $to, string $message): void
    {
        Log::info('WhatsApp Message (stub)', [
            'to' => $to,
            'message' => $message,
        ]);
    }

    public function sendAudio(string $to, string $mediaUrl, string $caption = ''): void
    {
        Log::info('WhatsApp Audio (stub)', [
            'to' => $to,
            'media_url' => $mediaUrl,
            'caption' => $caption,
        ]);
    }

    public function supportsAudio(): bool
    {
        // Stub provider doesn't actually support audio, but we can simulate it
        // In production, this would check the actual provider capabilities
        return false; // Return false so it falls back to sendMessage with link
    }
}

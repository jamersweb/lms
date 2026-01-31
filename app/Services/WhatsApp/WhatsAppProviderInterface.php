<?php

namespace App\Services\WhatsApp;

/**
 * Interface for WhatsApp providers (Twilio, custom API, etc.)
 */
interface WhatsAppProviderInterface
{
    /**
     * Send a text message via WhatsApp.
     */
    public function sendMessage(string $to, string $message): void;

    /**
     * Send an audio message via WhatsApp (for micro-nudges).
     */
    public function sendAudio(string $to, string $mediaUrl, string $caption = ''): void;

    /**
     * Check if the provider supports audio messages.
     */
    public function supportsAudio(): bool;
}

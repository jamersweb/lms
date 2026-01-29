<?php

namespace App\Services\WhatsApp;

interface WhatsAppChannel
{
    /**
     * Send a WhatsApp message to a recipient.
     *
     * @param string $to E.164 phone number or provider-specific identifier
     * @param string $message Message content
     */
    public function send(string $to, string $message): void;
}


<?php

namespace App\Jobs;

use App\Services\WhatsApp\TriggerWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued job to send a trigger to the WhatsApp webhook.
 * Use for async/bulk sends to avoid blocking the request.
 */
class SendTriggerJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $templateName,
        public string $message,
        public array $phoneNumbers,
        public string $language = 'en'
    ) {}

    public function handle(TriggerWebhookService $service): void
    {
        $service->send($this->templateName, $this->message, $this->phoneNumbers, $this->language);
    }
}

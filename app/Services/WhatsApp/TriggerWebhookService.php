<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends trigger payloads (template_name + message) to the WhatsApp webhook.
 * Supports single and bulk recipients. No auth required.
 */
class TriggerWebhookService
{
    public function __construct(
        private ?string $webhookUrl = null
    ) {
        $this->webhookUrl = $webhookUrl ?? $this->resolveWebhookUrl();
    }

    private function resolveWebhookUrl(): ?string
    {
        $fromSetting = \App\Models\AppSetting::where('key', 'whatsapp_trigger_webhook_url')->first()?->value;
        return $fromSetting ?: config('whatsapp.trigger_webhook_url');
    }

    /**
     * Send a trigger to the webhook.
     *
     * @param  string  $templateName  e.g. progress_reminder, certificate_delivery
     * @param  string  $message  The message body (with variables resolved)
     * @param  string|array  $recipients  Single phone (E.164) or array of phones for bulk
     * @param  string  $language  Template language code (default: en)
     * @return bool Success
     */
    public function send(string $templateName, string $message, string|array $recipients, string $language = 'en'): bool
    {
        $phoneNumbers = is_array($recipients) ? $recipients : [$recipients];
        $phoneNumbers = array_values(array_filter(array_map('trim', $phoneNumbers)));

        if (empty($phoneNumbers)) {
            Log::warning('TriggerWebhookService: No recipients provided', ['template' => $templateName]);
            return false;
        }

        if (empty($this->webhookUrl)) {
            Log::warning('TriggerWebhookService: Webhook URL not configured');
            return false;
        }

        $payload = [
            'template_name' => $templateName,
            'message' => $message,
            'phone_numbers' => $phoneNumbers,
            'language' => $language,
        ];

        try {
            $response = Http::timeout(15)
                ->asJson()
                ->post($this->webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('TriggerWebhookService: Sent successfully', [
                    'template' => $templateName,
                    'recipient_count' => count($phoneNumbers),
                ]);
                return true;
            }

            Log::error('TriggerWebhookService: Webhook returned error', [
                'template' => $templateName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('TriggerWebhookService: Request failed', [
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if the webhook is configured and reachable.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->webhookUrl);
    }
}

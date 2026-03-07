<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\TriggerWebhookService;
use Illuminate\Console\Command;

class TestTriggerCommand extends Command
{
    protected $signature = 'lms:trigger-test {--phone= : Phone number to test (E.164, e.g. +923001234567)}';
    protected $description = 'Test WhatsApp trigger webhook with a sample payload';

    public function handle(TriggerWebhookService $service): int
    {
        $phone = $this->option('phone');

        if (!$service->isConfigured()) {
            $this->error('WHATSAPP_TRIGGER_WEBHOOK_URL is not set in .env');
            $this->line('Add: WHATSAPP_TRIGGER_WEBHOOK_URL=https://cript.aingu.com/webhook-test/d19cf2b9-b225-4710-8e98-bec0ed2ab938');
            return Command::FAILURE;
        }

        $this->info('Webhook URL: ' . config('whatsapp.trigger_webhook_url'));

        if (!$phone) {
            $this->warn('No phone number provided. Use --phone=+923001234567 to send a real test.');
            $this->line('Sending dry-run (will fail at webhook if no recipient logic)...');
            $phone = '+0000000000'; // Placeholder - webhook may reject
        }

        $templateName = 'progress_reminder';
        $message = 'Only a few lessons remain 🌙 Complete them and finish this journey with barakah from Allah سُبْحَانَهُ وَتَعَالَى ✨🌿';

        $this->line("Template: {$templateName}");
        $this->line("Message: {$message}");
        $this->line("Phone: {$phone}");
        $this->newLine();

        try {
            $ok = $service->send($templateName, $message, $phone);
            if ($ok) {
                $this->info('✓ Trigger sent successfully. Check webhook logs / WhatsApp for delivery.');
                return Command::SUCCESS;
            }
            $this->error('✗ Webhook returned an error.');
            $this->line('Run: curl -X POST "' . config('whatsapp.trigger_webhook_url') . '" -H "Content-Type: application/json" -d \'{"template_name":"progress_reminder","message":"Test","phone_numbers":["' . $phone . '"],"language":"en"}\'');
            $this->line('Check storage/logs/laravel.log for full response.');
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error('✗ Failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

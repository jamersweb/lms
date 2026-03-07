<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Fires WhatsApp triggers based on app events.
 * Resolves template + message, checks opt-in, and sends via TriggerWebhookService.
 */
class TriggerService
{
    public function __construct(
        private TriggerWebhookService $webhookService
    ) {}

    /**
     * Fire a trigger for a single user.
     * Respects whatsapp_opt_in and whatsapp_number.
     */
    public function fire(string $eventKey, User $user, array $context = []): bool
    {
        if (!$user->whatsapp_opt_in || empty($user->whatsapp_number)) {
            return false;
        }

        $config = config("triggers.templates.{$eventKey}");
        if (!$config) {
            Log::warning("TriggerService: Unknown event key: {$eventKey}");
            return false;
        }

        $message = $this->resolveMessage($config['message'], array_merge([
            'name' => $user->name,
        ], $context));

        return $this->webhookService->send(
            $config['template_name'],
            $message,
            $user->whatsapp_number,
            $context['language'] ?? 'en'
        );
    }

    /**
     * Fire a trigger for multiple users (bulk).
     * Returns count of users actually sent to (opted-in with phone).
     */
    public function fireBulk(string $eventKey, iterable $users, array $context = []): int
    {
        $config = config("triggers.templates.{$eventKey}");
        if (!$config) {
            Log::warning("TriggerService: Unknown event key: {$eventKey}");
            return 0;
        }

        $phoneNumbers = [];
        foreach ($users as $user) {
            if ($user instanceof User && $user->whatsapp_opt_in && !empty($user->whatsapp_number)) {
                $phoneNumbers[] = $user->whatsapp_number;
            }
        }

        if (empty($phoneNumbers)) {
            return 0;
        }

        $message = $this->resolveMessage($config['message'], $context);
        $ok = $this->webhookService->send($config['template_name'], $message, $phoneNumbers, $context['language'] ?? 'en');

        return $ok ? count($phoneNumbers) : 0;
    }

    /**
     * Fire a trigger asynchronously (queued).
     * Respects whatsapp_opt_in and whatsapp_number.
     */
    public function fireAsync(string $eventKey, User $user, array $context = []): void
    {
        if (!$user->whatsapp_opt_in || empty($user->whatsapp_number)) {
            return;
        }

        $config = config("triggers.templates.{$eventKey}");
        if (!$config) {
            return;
        }

        $message = $this->resolveMessage($config['message'], array_merge(['name' => $user->name], $context));

        \App\Jobs\SendTriggerJob::dispatch(
            $config['template_name'],
            $message,
            [$user->whatsapp_number],
            $context['language'] ?? 'en'
        );
    }

    /**
     * Resolve placeholders in message: [Name], [Score], [Link], [Percent]
     */
    private function resolveMessage(string $template, array $context): string
    {
        $replacements = [
            '[Name]' => $context['name'] ?? '',
            '[Score]' => $context['score'] ?? '',
            '[Link]' => $context['link'] ?? '',
            '[Percent]' => $context['percent'] ?? '',
            '[Hours]' => $context['hours'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Check if we should fire first_login (only once per user).
     */
    public function shouldFireFirstLogin(User $user): bool
    {
        return !Cache::has("trigger_sent:first_login:{$user->id}");
    }

    /**
     * Mark first_login as sent.
     */
    public function markFirstLoginSent(User $user): void
    {
        Cache::forever("trigger_sent:first_login:{$user->id}", true);
    }

    /**
     * Check if we should fire video_halfway (once per user per lesson).
     */
    public function shouldFireVideoHalfway(User $user, int $lessonId): bool
    {
        return !Cache::has("trigger_sent:video_halfway:{$user->id}:{$lessonId}");
    }

    /**
     * Mark video_halfway as sent.
     */
    public function markVideoHalfwaySent(User $user, int $lessonId): void
    {
        Cache::forever("trigger_sent:video_halfway:{$user->id}:{$lessonId}", true);
    }

    /**
     * Check if we should fire progress_reminder (once per user per course).
     */
    public function shouldFireProgressReminder(User $user, int $courseId): bool
    {
        return !Cache::has("trigger_sent:progress_reminder:{$user->id}:{$courseId}");
    }

    /**
     * Mark progress_reminder as sent.
     */
    public function markProgressReminderSent(User $user, int $courseId): void
    {
        Cache::forever("trigger_sent:progress_reminder:{$user->id}:{$courseId}", true);
    }
}

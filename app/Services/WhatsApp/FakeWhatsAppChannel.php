<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FakeWhatsAppChannel implements WhatsAppChannel
{
    public const CACHE_KEY = 'whatsapp_fake_messages';

    public function send(string $to, string $message): void
    {
        $entry = [
            'to' => $to,
            'message' => $message,
            'sent_at' => now()->toDateTimeString(),
        ];

        $messages = Cache::get(self::CACHE_KEY, []);
        $messages[] = $entry;
        Cache::forever(self::CACHE_KEY, $messages);

        Log::info('FakeWhatsAppChannel send', $entry);
    }

    /**
     * For tests/dev: retrieve all stored fake messages.
     *
     * @return array<int, array{to:string,message:string,sent_at:string}>
     */
    public static function messages(): array
    {
        return Cache::get(self::CACHE_KEY, []);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}


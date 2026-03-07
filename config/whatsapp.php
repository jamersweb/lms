<?php

return [
    'provider' => env('WHATSAPP_PROVIDER', 'log'), // 'log', 'twilio', etc.
    'from' => env('WHATSAPP_FROM', ''),
    'api_key' => env('WHATSAPP_API_KEY', ''),
    'api_url' => env('WHATSAPP_API_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Trigger Webhook (Meta / cript.aingu.com)
    |--------------------------------------------------------------------------
    | Sends template_name + message to webhook for WhatsApp delivery.
    | Supports single and bulk recipients. No auth required.
    */
    'trigger_webhook_url' => env('WHATSAPP_TRIGGER_WEBHOOK_URL', ''),

    // AppSetting (whatsapp_trigger_webhook_url) overrides when set in admin WhatsApp Settings
];

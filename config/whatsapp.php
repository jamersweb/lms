<?php

return [
    'provider' => env('WHATSAPP_PROVIDER', 'log'), // 'log', 'twilio', etc.
    'from' => env('WHATSAPP_FROM', ''),
    'api_key' => env('WHATSAPP_API_KEY', ''),
    'api_url' => env('WHATSAPP_API_URL', ''),
];

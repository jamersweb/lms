<?php

return [
    'enabled' => env('NOTIFICATIONS_ENABLED', true),

    'channels' => [
        'email' => env('NOTIFICATIONS_EMAIL_ENABLED', true),
        'whatsapp' => env('NOTIFICATIONS_WHATSAPP_ENABLED', true),
    ],

    'drip' => [
        'enabled' => env('NOTIFICATIONS_DRIP_ENABLED', true),
        'send_hour' => env('NOTIFICATIONS_DRIP_HOUR', 9), // local app timezone
    ],

    'task' => [
        'enabled' => env('NOTIFICATIONS_TASK_ENABLED', true),
        'send_hour' => env('NOTIFICATIONS_TASK_HOUR', 19),
    ],

    'stagnation' => [
        'enabled' => env('NOTIFICATIONS_STAGNATION_ENABLED', true),
        'inactive_days' => env('NOTIFICATIONS_STAGNATION_DAYS', 3),
        'send_hour' => env('NOTIFICATIONS_STAGNATION_HOUR', 10),
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Channel Driver
    |--------------------------------------------------------------------------
    |
    | For now we only support a "fake" driver that stores messages in cache
    | and logs them. In the future this can be extended to twilio, cloud_api, etc.
    |
    */
    'driver' => env('WHATSAPP_DRIVER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Default "from" identifier
    |--------------------------------------------------------------------------
    */
    'from' => env('WHATSAPP_FROM', null),
];


<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Service Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk WhatsApp service yang digunakan untuk mengirim
    | pesan reset password dan notifikasi lainnya.
    |
    */

    'enabled' => env('WHATSAPP_ENABLED', false),

    'url' => env('WHATSAPP_SERVICE_URL', 'https://wa-api.lembagabahasa.site'),

    'api_key' => env('WHATSAPP_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Timeout Settings
    |--------------------------------------------------------------------------
    */

    'timeout' => env('WHATSAPP_TIMEOUT', 15),

    'retry' => env('WHATSAPP_RETRY', 1),

    'outbound_spacing_seconds' => env('WHATSAPP_OUTBOUND_SPACING_SECONDS', 50),
];

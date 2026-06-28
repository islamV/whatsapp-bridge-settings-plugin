<?php

return [
    'provider' => env('WHATSAPP_PROVIDER', 'default'),

    'api_base_url' => env('WHATSAPP_API_BASE_URL'),

    'api_token' => env('WHATSAPP_API_TOKEN'),

    'sender' => env('WHATSAPP_SENDER'),

    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '20'),

    'otp_enabled' => env('WHATSAPP_OTP_ENABLED', true),

    'messages_enabled' => env('WHATSAPP_MESSAGES_ENABLED', true),

    'timeout' => env('WHATSAPP_TIMEOUT', 30),

    'otp_template' => env(
        'WHATSAPP_OTP_TEMPLATE',
        'Your verification code is: {otp}'
    ),

    'log_channel' => env('WHATSAPP_LOG_CHANNEL', null),
];

<?php

return [
    'active_provider' => env('WHATSAPP_ACTIVE_PROVIDER', 'bridge'),

    'providers' => [
        'bridge' => [
            'api_base_url' => env('WHATSAPP_BRIDGE_API_BASE_URL'),
            'api_token' => env('WHATSAPP_BRIDGE_API_TOKEN'),
            'sender' => env('WHATSAPP_BRIDGE_SENDER'),
            'timeout' => env('WHATSAPP_BRIDGE_TIMEOUT', 30),
        ],

        'meta' => [
            'phone_number_id' => env('WHATSAPP_META_PHONE_NUMBER_ID'),
            'access_token' => env('WHATSAPP_META_ACCESS_TOKEN'),
            'business_account_id' => env('WHATSAPP_META_BUSINESS_ACCOUNT_ID'),
            'verify_token' => env('WHATSAPP_META_VERIFY_TOKEN'),
            'app_secret' => env('WHATSAPP_META_APP_SECRET'),
            'timeout' => env('WHATSAPP_META_TIMEOUT', 30),
        ],

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from_number' => env('TWILIO_FROM_NUMBER'),
            'timeout' => env('TWILIO_TIMEOUT', 30),
        ],
    ],

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

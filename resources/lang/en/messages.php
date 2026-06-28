<?php

return [
    'navigation_group' => 'Settings',
    'navigation_label' => 'WhatsApp',
    'page_title' => 'WhatsApp Bridge Settings',
    'page_heading' => 'WhatsApp Bridge Settings',

    'sections' => [
        'api_configuration' => 'API Configuration',
        'message_settings' => 'Message Settings',
        'message_templates' => 'Message Templates',
    ],

    'fields' => [
        'provider_name' => 'Provider Name',
        'provider_name_placeholder' => 'default',
        'api_base_url' => 'API Base URL',
        'api_base_url_placeholder' => 'https://api.whatsapp.example.com',
        'api_token' => 'API Token / Access Token',
        'api_token_placeholder_keep' => 'Leave blank to keep current token',
        'api_token_placeholder_enter' => 'Enter API token',
        'sender' => 'Sender / Instance ID / Phone Number ID',
        'sender_placeholder' => 'Sender ID',
        'default_country_code' => 'Default Country Code',
        'default_country_code_placeholder' => '20',
        'timeout' => 'Timeout (seconds)',
        'otp_enabled' => 'Enable OTP Messages',
        'messages_enabled' => 'Enable Normal Messages',
        'otp_template' => 'OTP Message Template',
        'otp_template_placeholder' => 'Your verification code is: {otp}',
        'otp_template_helper' => 'Use {otp} as a placeholder for the OTP code.',
    ],

    'actions' => [
        'save' => 'Save Settings',
        'send_test' => 'Send Test Message',
    ],

    'test_form' => [
        'phone' => 'Test Phone Number',
        'phone_placeholder' => '201000000000',
        'message' => 'Test Message',
        'message_placeholder' => 'Hello from WhatsApp Bridge!',
    ],

    'notifications' => [
        'saved' => 'WhatsApp settings saved successfully.',
        'test_sent' => 'Test message sent successfully!',
        'test_failed' => 'Failed to send test message. Check the logs for details.',
    ],

    'qr' => [
        'qr_scan_title' => 'Scan QR Code with WhatsApp',
        'qr_expires_in' => 'QR code expires in :count seconds',
        'qr_generating' => 'Generating QR code...',
        'disconnect' => 'Disconnect',
        'connected_title' => 'WhatsApp Connected',
        'connected_phone' => 'Connected phone: :phone',
        'disconnect_button' => 'Disconnect',
        'test_section_title' => 'Send Test Message',
        'test_phone_label' => 'Phone Number',
        'test_phone_placeholder' => '201000000000',
        'test_message_label' => 'Message',
        'test_sending' => 'Sending...',
        'test_send_button' => 'Send Test Message',
        'connect_button' => 'Connect WhatsApp',
        'description' => 'Generate a QR code to link your WhatsApp account. Open WhatsApp on your phone, go to Settings > Linked Devices > Link a Device.',
    ],
];

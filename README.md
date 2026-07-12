# WhatsApp Bridge Settings Plugin

[![Tests](https://github.com/islamV/whatsapp-bridge-settings-plugin/actions/workflows/tests.yml/badge.svg)](https://github.com/islamV/whatsapp-bridge-settings-plugin/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/islamv/whatsapp-bridge-settings-plugin.svg)](https://packagist.org/packages/islamv/whatsapp-bridge-settings-plugin)
[![License](https://img.shields.io/github/license/islamV/whatsapp-bridge-settings-plugin.svg)](https://github.com/islamV/whatsapp-bridge-settings-plugin/blob/main/LICENSE)

A reusable WhatsApp bridge and settings plugin for Laravel 13+ and Filament 5. Supports multiple providers: **Bridge**, **Meta WhatsApp Cloud API**, and **Twilio WhatsApp**.

## Features

- **Multi-provider support** - Bridge, Meta, Twilio with per-provider configuration
- **Provider selection** - Enum-based provider switching with labels and colors
- **QR Code pairing** - Connect WhatsApp via QR code (Bridge provider)
- **Connection status** - Real-time connection monitoring with polling
- **Test messaging** - Send test messages from the settings page
- **Encrypted tokens** - API tokens encrypted at rest in the database
- **OTP support** - Built-in OTP message templating
- **Bilingual** - Full English and Arabic translations

## Installation

### 1. Install via Composer

```bash
composer require islamv/whatsapp-bridge-settings-plugin
```

### 2. Publish config

```bash
php artisan vendor:publish --tag=whatsapp-bridge-settings-config
```

### 3. Publish migrations

```bash
php artisan vendor:publish --tag=whatsapp-bridge-settings-migrations
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Register the Filament plugin

Add the plugin to your Filament PanelProvider:

```php
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(WhatsappBridgeSettingsPlugin::make());
}
```

### 6. Configure WhatsApp credentials

Open the **WhatsApp Settings** page in your Filament admin panel and configure your preferred provider. If you are using the local sibling service `whatsapp-bridge`, the Bridge provider defaults to `http://127.0.0.1:3000`.

## Supported Providers

### WhatsApp Bridge (Self-hosted)

Self-hosted WhatsApp Web bridge with QR code pairing support.

```env
WHATSAPP_ACTIVE_PROVIDER=bridge
WHATSAPP_BRIDGE_API_BASE_URL=http://127.0.0.1:3000
WHATSAPP_BRIDGE_API_TOKEN=your-token
WHATSAPP_BRIDGE_SENDER=your-sender-id
WHATSAPP_BRIDGE_TIMEOUT=30
```

### Meta WhatsApp Cloud API

Official Meta WhatsApp Business Cloud API.

```env
WHATSAPP_ACTIVE_PROVIDER=meta
WHATSAPP_META_PHONE_NUMBER_ID=your-phone-number-id
WHATSAPP_META_ACCESS_TOKEN=your-access-token
WHATSAPP_META_BUSINESS_ACCOUNT_ID=your-business-account-id
WHATSAPP_META_VERIFY_TOKEN=your-verify-token
WHATSAPP_META_APP_SECRET=your-app-secret
WHATSAPP_META_TIMEOUT=30
```

### Twilio WhatsApp

Twilio WhatsApp Messaging API.

```env
WHATSAPP_ACTIVE_PROVIDER=twilio
TWILIO_ACCOUNT_SID=your-account-sid
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_FROM_NUMBER=+1234567890
TWILIO_TIMEOUT=30
```

### Common Settings

```env
WHATSAPP_DEFAULT_COUNTRY_CODE=20
WHATSAPP_OTP_ENABLED=true
WHATSAPP_MESSAGES_ENABLED=true
WHATSAPP_TIMEOUT=30
WHATSAPP_OTP_TEMPLATE="Your verification code is: {otp}"
WHATSAPP_LOG_CHANNEL=stack
```

## Usage

### Send a normal message via Facade

```php
use Islamv\WhatsappBridgeSettingsPlugin\Facades\WhatsappBridge;

WhatsappBridge::sendMessage('201000000000', 'Hello from Laravel!');
```

### Send an OTP via Facade

```php
use Islamv\WhatsappBridgeSettingsPlugin\Facades\WhatsappBridge;

$otp = '123456';

WhatsappBridge::sendOtp('201000000000', $otp);
```

### Using the Enum

```php
use Islamv\WhatsappBridgeSettingsPlugin\Enums\WhatsappProvider;

// Get provider label
WhatsappProvider::Bridge->getLabel(); // 'WhatsApp Bridge'

// Get provider color
WhatsappProvider::Meta->getColor(); // 'info'

// Iterate all providers
foreach (WhatsappProvider::cases() as $provider) {
    echo $provider->getLabel();
}
```

### Using Dependency Injection

```php
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;

class SendOrderConfirmation
{
    public function __construct(
        protected WhatsappProviderInterface $whatsapp
    ) {}

    public function handle(): void
    {
        $this->whatsapp->sendMessage(
            '201000000000',
            'Your order is confirmed.'
        );
    }
}
```

### Using the OTP Sender

```php
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappOtpSender;

class SendVerificationCode
{
    public function __construct(
        protected WhatsappOtpSender $otpSender
    ) {}

    public function handle(): void
    {
        $otp = random_int(100000, 999999);

        $this->otpSender->send('201000000000', (string) $otp);
    }
}
```

## Architecture

```
src/
├── WhatsappBridgeSettingsPlugin.php          # Filament plugin registration
├── WhatsappBridgeSettingsPluginServiceProvider.php
├── Contracts/
│   └── WhatsappProviderInterface.php         # Provider contract
├── Enums/
│   └── WhatsappProvider.php                  # Provider enum with label and color
├── Concerns/
│   ├── ManagesPhoneNumbers.php               # Phone normalization and masking
│   ├── HasLogChannel.php                     # Log channel resolution
│   └── HandlesOtpMessages.php                # OTP template rendering
├── Services/
│   ├── WhatsappBridge.php                    # Bridge HTTP implementation
│   ├── MetaWhatsapp.php                      # Meta Cloud API implementation
│   ├── TwilioWhatsapp.php                    # Twilio API implementation
│   └── WhatsappOtpSender.php                 # OTP sending service
├── Settings/
│   └── WhatsappSettingsRepository.php        # Multi-provider settings storage
├── Filament/
│   └── Pages/
│       └── WhatsappSettingsPage.php          # Filament settings page
└── Facades/
    └── WhatsappBridge.php                    # Facade

resources/
├── lang/
│   ├── en/messages.php
│   └── ar/messages.php
└── views/

config/
└── whatsapp-bridge-settings.php

database/
└── migrations/
    └── create_whatsapp_bridge_settings_table.php

tests/
├── ConfigTest.php
├── ServiceProviderTest.php
├── ProviderSelectionTest.php
├── WhatsappBridgeTest.php
├── MetaWhatsappTest.php
├── TwilioWhatsappTest.php
├── WhatsappOtpSenderTest.php
└── WhatsappSettingsRepositoryTest.php
```

## Testing

```bash
vendor/bin/phpunit
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for recent changes.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines.

## Security

See [SECURITY.md](SECURITY.md) for security policies and vulnerability reporting.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.

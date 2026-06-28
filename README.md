# WhatsApp Bridge Settings Plugin

A reusable WhatsApp bridge and settings plugin for Laravel 13+ and Filament 5.

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

Open the **WhatsApp Settings** page in your Filament admin panel and configure:

- API Base URL
- API Token
- Sender / Instance ID
- Default Country Code
- OTP Template
- Timeout

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

## Configuration

### Environment variables

```env
WHATSAPP_PROVIDER=default
WHATSAPP_API_BASE_URL=https://api.whatsapp.example.com
WHATSAPP_API_TOKEN=your-api-token
WHATSAPP_SENDER=your-sender-id
WHATSAPP_DEFAULT_COUNTRY_CODE=20
WHATSAPP_OTP_ENABLED=true
WHATSAPP_MESSAGES_ENABLED=true
WHATSAPP_TIMEOUT=30
WHATSAPP_OTP_TEMPLATE="Your verification code is: {otp}"
WHATSAPP_LOG_CHANNEL=stack
```

### Config file

Published to `config/whatsapp-bridge-settings.php`.

## Extending

### Custom provider implementation

Create your own provider by implementing `WhatsappProviderInterface`:

```php
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;

class MyCustomWhatsappProvider implements WhatsappProviderInterface
{
    public function sendMessage(string $to, string $message, array $options = []): bool
    {
        // Your custom implementation
    }

    public function sendOtp(string $to, string $otp, array $options = []): bool
    {
        return $this->sendMessage($to, "Your code: $otp");
    }
}
```

Then bind it in a service provider:

```php
$this->app->bind(
    \Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface::class,
    \App\Services\MyCustomWhatsappProvider::class
);
```

## Architecture

```
src/
├── WhatsappBridgeSettingsPlugin.php          # Filament 5 Plugin class
├── WhatsappBridgeSettingsPluginServiceProvider.php  # Service provider
├── Contracts/
│   └── WhatsappProviderInterface.php         # Service contract
├── Services/
│   ├── WhatsappBridge.php                    # Default HTTP bridge implementation
│   └── WhatsappOtpSender.php                 # OTP sending service
├── Settings/
│   └── WhatsappSettingsRepository.php        # Settings storage (DB + config)
├── Filament/
│   └── Pages/
│       └── WhatsappSettingsPage.php          # Filament settings page
└── Facades/
    └── Whatsapp.php                          # Facade
```

## Testing

```bash
vendor/bin/phpunit
```

## Security

- API tokens are encrypted at rest in the database
- Full tokens are never displayed in the UI after saving
- Error messages never expose credentials
- Phone numbers are masked in logs
# whatsapp-bridge-settings-plugin

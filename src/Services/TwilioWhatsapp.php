<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\HandlesOtpMessages;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\HasLogChannel;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\ManagesPhoneNumbers;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

class TwilioWhatsapp implements WhatsappProviderInterface
{
    use HandlesOtpMessages;
    use HasLogChannel;
    use ManagesPhoneNumbers;

    public function __construct(
        protected WhatsappSettingsRepository $settings
    ) {}

    public function sendMessage(string $to, string $message, array $options = []): bool
    {
        $config = $this->settings->getProviderConfig('twilio');

        if (! $config['account_sid'] || ! $config['auth_token']) {
            Log::channel($this->logChannel())->warning('Twilio WhatsApp not configured');

            return false;
        }

        $phone = $this->normalizePhone($to, $this->getDefaultCountryCode());

        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json";

            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withBasicAuth($config['account_sid'], $config['auth_token'])
                ->asForm()
                ->post($url, [
                    'To' => "whatsapp:{$phone}",
                    'From' => "whatsapp:{$config['from_number']}",
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::channel($this->logChannel())->warning('Twilio sendMessage failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'to' => $this->maskPhone($phone),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::channel($this->logChannel())->error('Twilio sendMessage exception', [
                'message' => $e->getMessage(),
                'to' => $this->maskPhone($phone),
            ]);

            return false;
        }
    }

    public function sendOtp(string $to, string $otp, array $options = []): bool
    {
        $message = $this->buildOtpMessage($otp);

        if ($message === null) {
            return false;
        }

        return $this->sendMessage($to, $message, $options);
    }

    public function getConnectionStatus(): string
    {
        $config = $this->settings->getProviderConfig('twilio');

        if (! $config['account_sid'] || ! $config['auth_token']) {
            return 'disconnected';
        }

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withBasicAuth($config['account_sid'], $config['auth_token'])
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}.json");

            return $response->successful() ? 'connected' : 'disconnected';
        } catch (\Throwable) {
            return 'disconnected';
        }
    }

    public function generateQrCode(): ?string
    {
        return null;
    }

    public function disconnect(): bool
    {
        return true;
    }

    public function getConnectedPhone(): ?string
    {
        $config = $this->settings->getProviderConfig('twilio');

        return $config['from_number'] ?? null;
    }

    protected function getDefaultCountryCode(): string
    {
        return (string) $this->settings->get('default_country_code', '20');
    }
}

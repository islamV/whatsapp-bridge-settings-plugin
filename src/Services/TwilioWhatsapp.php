<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

class TwilioWhatsapp implements WhatsappProviderInterface
{
    public function sendMessage(string $to, string $message, array $options = []): bool
    {
        $config = $this->getConfig();

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
        $settings = app(WhatsappSettingsRepository::class);

        if (! $settings->get('otp_enabled', true)) {
            return false;
        }

        $template = $settings->get('otp_template', 'Your verification code is: {otp}');
        $message = str_replace('{otp}', $otp, (string) $template);

        return $this->sendMessage($to, $message, $options);
    }

    public function getConnectionStatus(): string
    {
        $config = $this->getConfig();

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
        $config = $this->getConfig();

        return $config['from_number'] ?? null;
    }

    protected function getConfig(): array
    {
        $settings = app(WhatsappSettingsRepository::class);

        return $settings->getProviderConfig('twilio');
    }

    protected function getDefaultCountryCode(): string
    {
        $settings = app(WhatsappSettingsRepository::class);

        return (string) $settings->get('default_country_code', '20');
    }

    protected function normalizePhone(string $phone, string $defaultCountryCode): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($cleaned === '') {
            return $phone;
        }

        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }

        if (strlen($cleaned) <= 10 && ! str_starts_with($cleaned, '00')) {
            $cleaned = ltrim($cleaned, '0');
            $cleaned = $defaultCountryCode . $cleaned;
        }

        return '+' . $cleaned;
    }

    protected function maskPhone(string $phone): string
    {
        $len = strlen($phone);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($phone, 0, 4) . str_repeat('*', $len - 8) . substr($phone, -4);
    }

    private function logChannel(): string
    {
        $settings = app(WhatsappSettingsRepository::class);

        return $settings->get('log_channel') ?? config('logging.default', 'stack');
    }
}

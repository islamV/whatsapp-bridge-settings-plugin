<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

class MetaWhatsapp implements WhatsappProviderInterface
{
    protected string $apiVersion = 'v18.0';

    public function sendMessage(string $to, string $message, array $options = []): bool
    {
        $config = $this->getConfig();

        if (! $config['phone_number_id'] || ! $config['access_token']) {
            Log::channel($this->logChannel())->warning('Meta WhatsApp not configured');

            return false;
        }

        $phone = $this->normalizePhone($to, $this->getDefaultCountryCode());

        try {
            $url = "https://graph.facebook.com/{$this->apiVersion}/{$config['phone_number_id']}/messages";

            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withToken($config['access_token'])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::channel($this->logChannel())->warning('Meta sendMessage failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'to' => $this->maskPhone($phone),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::channel($this->logChannel())->error('Meta sendMessage exception', [
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

        if (! $config['phone_number_id'] || ! $config['access_token']) {
            return 'disconnected';
        }

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withToken($config['access_token'])
                ->get("https://graph.facebook.com/{$this->apiVersion}/{$config['phone_number_id']}");

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

        return $config['phone_number_id'] ?? null;
    }

    public function setApiVersion(string $version): static
    {
        $this->apiVersion = $version;

        return $this;
    }

    protected function getConfig(): array
    {
        $settings = app(WhatsappSettingsRepository::class);

        return $settings->getProviderConfig('meta');
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

        return $cleaned;
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

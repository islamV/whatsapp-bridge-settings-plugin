<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

class WhatsappBridge implements WhatsappProviderInterface
{
    public function sendMessage(string $to, string $message, array $options = []): bool
    {
        $settings = app(WhatsappSettingsRepository::class);

        if (! $settings->get('api_base_url') || ! $settings->get('api_token')) {
            Log::channel($this->logChannel($settings))->warning('WhatsApp bridge not configured');

            return false;
        }

        $phone = $this->normalizePhone(
            $to,
            $settings->get('default_country_code', '20')
        );

        try {
            $response = Http::timeout((int) ($settings->get('timeout', 30)))
                ->withToken((string) $settings->get('api_token'))
                ->post(rtrim((string) $settings->get('api_base_url'), '/') . '/messages', [
                    'to' => $phone,
                    'text' => $message,
                    'sender' => $settings->get('sender'),
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::channel($this->logChannel($settings))->warning('WhatsApp sendMessage failed', [
                'status' => $response->status(),
                'to' => $this->maskPhone($phone),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::channel($this->logChannel($settings))->error('WhatsApp sendMessage exception', [
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
        $settings = app(WhatsappSettingsRepository::class);

        if (! $settings->get('api_base_url') || ! $settings->get('api_token')) {
            return 'disconnected';
        }

        try {
            $response = Http::timeout((int) ($settings->get('timeout', 30)))
                ->withToken((string) $settings->get('api_token'))
                ->get(rtrim((string) $settings->get('api_base_url'), '/') . '/status');

            if ($response->successful()) {
                $data = $response->json();

                return ($data['status'] ?? '') === 'connected' ? 'connected' : 'disconnected';
            }

            return 'disconnected';
        } catch (\Throwable $e) {
            return 'disconnected';
        }
    }

    public function generateQrCode(): ?string
    {
        $settings = app(WhatsappSettingsRepository::class);

        if (! $settings->get('api_base_url') || ! $settings->get('api_token')) {
            return null;
        }

        try {
            $response = Http::timeout((int) ($settings->get('timeout', 30)))
                ->withToken((string) $settings->get('api_token'))
                ->post(rtrim((string) $settings->get('api_base_url'), '/') . '/qr');

            if ($response->successful()) {
                $data = $response->json();

                return $data['qr'] ?? null;
            }

            return null;
        } catch (\Throwable $e) {
            Log::channel($this->logChannel($settings))->error('WhatsApp generateQrCode exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function disconnect(): bool
    {
        $settings = app(WhatsappSettingsRepository::class);

        if (! $settings->get('api_base_url') || ! $settings->get('api_token')) {
            return false;
        }

        try {
            $response = Http::timeout((int) ($settings->get('timeout', 30)))
                ->withToken((string) $settings->get('api_token'))
                ->post(rtrim((string) $settings->get('api_base_url'), '/') . '/disconnect');

            return $response->successful();
        } catch (\Throwable $e) {
            Log::channel($this->logChannel($settings))->error('WhatsApp disconnect exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getConnectedPhone(): ?string
    {
        $settings = app(WhatsappSettingsRepository::class);

        if (! $settings->get('api_base_url') || ! $settings->get('api_token')) {
            return null;
        }

        try {
            $response = Http::timeout((int) ($settings->get('timeout', 30)))
                ->withToken((string) $settings->get('api_token'))
                ->get(rtrim((string) $settings->get('api_base_url'), '/') . '/status');

            if ($response->successful()) {
                $data = $response->json();

                return $data['phone'] ?? null;
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizePhone(string $phone, ?string $defaultCountryCode = null): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($cleaned === '') {
            return $phone;
        }

        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }

        $defaultCountryCode = $defaultCountryCode ?? '20';

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

    private function logChannel(WhatsappSettingsRepository $settings): string
    {
        return $settings->get('log_channel') ?? config('logging.default', 'stack');
    }
}

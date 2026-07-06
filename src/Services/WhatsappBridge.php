<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\HandlesOtpMessages;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\HasLogChannel;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\ManagesPhoneNumbers;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

class WhatsappBridge implements WhatsappProviderInterface
{
    use HandlesOtpMessages;
    use HasLogChannel;
    use ManagesPhoneNumbers;

    public function __construct(
        protected WhatsappSettingsRepository $settings
    ) {}

    public function sendMessage(string $to, string $message, array $options = []): bool
    {
        $config = $this->settings->getProviderConfig('bridge');

        if (! $config['api_base_url'] || ! $config['api_token']) {
            Log::channel($this->logChannel())->warning('WhatsApp bridge not configured');

            return false;
        }

        $phone = $this->normalizePhone($to, $this->getDefaultCountryCode());

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withToken((string) $config['api_token'])
                ->post(rtrim((string) $config['api_base_url'], '/') . '/messages', [
                    'to' => $phone,
                    'text' => $message,
                    'sender' => $config['sender'] ?? null,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::channel($this->logChannel())->warning('WhatsApp sendMessage failed', [
                'status' => $response->status(),
                'to' => $this->maskPhone($phone),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::channel($this->logChannel())->error('WhatsApp sendMessage exception', [
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
        $config = $this->settings->getProviderConfig('bridge');

        if (! $config['api_base_url'] || ! $config['api_token']) {
            return 'disconnected';
        }

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withToken((string) $config['api_token'])
                ->get(rtrim((string) $config['api_base_url'], '/') . '/status');

            if ($response->successful()) {
                $data = $response->json();

                return ($data['status'] ?? '') === 'connected' ? 'connected' : 'disconnected';
            }

            return 'disconnected';
        } catch (\Throwable) {
            return 'disconnected';
        }
    }

    public function generateQrCode(): ?string
    {
        $config = $this->settings->getProviderConfig('bridge');

        if (! $config['api_base_url'] || ! $config['api_token']) {
            return null;
        }

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withToken((string) $config['api_token'])
                ->post(rtrim((string) $config['api_base_url'], '/') . '/qr');

            if ($response->successful()) {
                $data = $response->json();

                return $data['qr'] ?? null;
            }

            return null;
        } catch (\Throwable $e) {
            Log::channel($this->logChannel())->error('WhatsApp generateQrCode exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function disconnect(): bool
    {
        $config = $this->settings->getProviderConfig('bridge');

        if (! $config['api_base_url'] || ! $config['api_token']) {
            return false;
        }

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withToken((string) $config['api_token'])
                ->post(rtrim((string) $config['api_base_url'], '/') . '/disconnect');

            return $response->successful();
        } catch (\Throwable $e) {
            Log::channel($this->logChannel())->error('WhatsApp disconnect exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getConnectedPhone(): ?string
    {
        $config = $this->settings->getProviderConfig('bridge');

        if (! $config['api_base_url'] || ! $config['api_token']) {
            return null;
        }

        try {
            $response = Http::timeout((int) ($config['timeout'] ?? 30))
                ->withToken((string) $config['api_token'])
                ->get(rtrim((string) $config['api_base_url'], '/') . '/status');

            if ($response->successful()) {
                $data = $response->json();

                return $data['phone'] ?? null;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function getDefaultCountryCode(): string
    {
        return (string) $this->settings->get('default_country_code', '20');
    }
}

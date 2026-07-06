<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\HandlesOtpMessages;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\HasLogChannel;
use Islamv\WhatsappBridgeSettingsPlugin\Concerns\ManagesPhoneNumbers;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

class MetaWhatsapp implements WhatsappProviderInterface
{
    use HandlesOtpMessages;
    use HasLogChannel;
    use ManagesPhoneNumbers;

    protected string $apiVersion = 'v18.0';

    public function __construct(
        protected WhatsappSettingsRepository $settings
    ) {}

    public function sendMessage(string $to, string $message, array $options = []): bool
    {
        $config = $this->settings->getProviderConfig('meta');

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
        $message = $this->buildOtpMessage($otp);

        if ($message === null) {
            return false;
        }

        return $this->sendMessage($to, $message, $options);
    }

    public function getConnectionStatus(): string
    {
        $config = $this->settings->getProviderConfig('meta');

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
        $config = $this->settings->getProviderConfig('meta');

        return $config['phone_number_id'] ?? null;
    }

    public function setApiVersion(string $version): static
    {
        $this->apiVersion = $version;

        return $this;
    }

    protected function getDefaultCountryCode(): string
    {
        return (string) $this->settings->get('default_country_code', '20');
    }
}

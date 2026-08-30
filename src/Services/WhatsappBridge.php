<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
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
            $response = $this->requestWithFallback(
                $config,
                fn () => Http::timeout((int) ($config['timeout'] ?? 30))
                    ->post($this->buildSessionUrl($config, 'send-message'), [
                        'phone' => $phone,
                        'message' => $message,
                    ]),
                fn () => Http::timeout((int) ($config['timeout'] ?? 30))
                    ->withToken((string) $config['api_token'])
                    ->post(rtrim((string) $config['api_base_url'], '/') . '/messages', [
                        'to' => $phone,
                        'text' => $message,
                        'sender' => $config['sender'] ?? null,
                    ])
            );

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

    /**
     * Check whether the bridge Node.js service itself is reachable via the
     * unauthenticated GET /health endpoint. Returns an array:
     *   ['reachable' => bool, 'status' => string|null, 'latency_ms' => int|null, 'url' => string|null]
     *
     * Pass $baseUrl to check a specific URL (e.g. from an unsaved form field)
     * instead of reading the stored setting.
     */
    public function checkBridgeHealth(?string $baseUrl = null): array
    {
        if ($baseUrl === null) {
            $config  = $this->settings->getProviderConfig('bridge');
            $baseUrl = $config['api_base_url'] ?? null;
        }

        if (empty($baseUrl)) {
            return ['reachable' => false, 'status' => null, 'latency_ms' => null, 'url' => null];
        }

        $url = rtrim((string) $baseUrl, '/') . '/health';

        try {
            $start = microtime(true);

            $response = Http::timeout(10)->get($url);

            $latency = (int) round((microtime(true) - $start) * 1000);

            if ($response->successful()) {
                $body = $response->json();

                return [
                    'reachable'  => true,
                    'status'     => $body['status'] ?? 'ok',
                    'latency_ms' => $latency,
                    'url'        => $url,
                ];
            }

            return ['reachable' => false, 'status' => null, 'latency_ms' => $latency, 'url' => $url];
        } catch (\Throwable $e) {
            Log::channel($this->logChannel())->debug('WhatsApp bridge /health check failed', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);

            return ['reachable' => false, 'status' => null, 'latency_ms' => null, 'url' => $url];
        }
    }

    public function getConnectionStatus(): string
    {
        $config = $this->settings->getProviderConfig('bridge');

        if (! $config['api_base_url'] || ! $config['api_token']) {
            return 'disconnected';
        }

        try {
            $response = $this->requestWithFallback(
                $config,
                fn () => Http::timeout((int) ($config['timeout'] ?? 30))
                    ->get($this->buildSessionUrl($config, 'check-connection-session')),
                fn () => Http::timeout((int) ($config['timeout'] ?? 30))
                    ->withToken((string) $config['api_token'])
                    ->get(rtrim((string) $config['api_base_url'], '/') . '/status')
            );

            if ($response->successful()) {
                return $this->normalizeBridgeStatus($response->json());
            }

            return 'disconnected';
        } catch (\Throwable) {
            return 'disconnected';
        }
    }

    /**
     * Attempt to obtain a QR code from the bridge.
     *
     * Returns an array describing the outcome:
     *   ['qrcode' => string|null, 'reason' => 'qr'|'connected'|'not_configured'|'unreachable'|'auth_failed'|'timeout'|'error']
     */
    public function generateQrCodeResult(): array
    {
        $config = $this->settings->getProviderConfig('bridge');

        if (empty($config['api_base_url']) || empty($config['api_token'])) {
            return ['qrcode' => null, 'reason' => 'not_configured'];
        }

        // QR generation blocks until whatsapp-web.js fires the `qr` event –
        // this can take 15-30 s. Use a much longer timeout than regular calls.
        $configuredTimeout = (int) ($config['timeout'] ?? 30);
        $qrTimeout = max(90, $configuredTimeout * 3);

        try {
            $response = $this->requestWithFallback(
                $config,
                fn () => Http::timeout($qrTimeout)
                    ->post($this->buildSessionUrl($config, 'generate-token')),
                fn () => Http::timeout($qrTimeout)
                    ->withToken((string) $config['api_token'])
                    ->post(rtrim((string) $config['api_base_url'], '/') . '/qr')
            );

            $status = $response->status();

            if ($response->successful()) {
                $data   = $response->json();
                $qrcode = $data['qrcode'] ?? $data['qr'] ?? null;

                if ($qrcode === null) {
                    // Bridge already authenticated – no QR needed.
                    return ['qrcode' => null, 'reason' => 'connected'];
                }

                return ['qrcode' => $qrcode, 'reason' => 'qr'];
            }

            $reason = match ($status) {
                401, 403 => 'auth_failed',
                408       => 'timeout',
                default   => 'error',
            };

            Log::channel($this->logChannel())->warning('WhatsApp generateQrCode non-success', [
                'http_status' => $status,
                'body'        => $response->body(),
                'reason'      => $reason,
            ]);

            return ['qrcode' => null, 'reason' => $reason];

        } catch (ConnectionException $e) {
            Log::channel($this->logChannel())->error('WhatsApp generateQrCode connection error', [
                'message' => $e->getMessage(),
            ]);

            return ['qrcode' => null, 'reason' => 'unreachable'];

        } catch (\Throwable $e) {
            Log::channel($this->logChannel())->error('WhatsApp generateQrCode exception', [
                'message' => $e->getMessage(),
            ]);

            return ['qrcode' => null, 'reason' => 'error'];
        }
    }

    /**
     * Interface-compatible wrapper. Returns the QR code string or null.
     * Prefer generateQrCodeResult() when calling from WhatsappBridge directly.
     */
    public function generateQrCode(): ?string
    {
        return $this->generateQrCodeResult()['qrcode'];
    }

    public function disconnect(): bool
    {
        $config = $this->settings->getProviderConfig('bridge');

        if (! $config['api_base_url'] || ! $config['api_token']) {
            return false;
        }

        try {
            $response = $this->requestWithFallback(
                $config,
                fn () => Http::timeout((int) ($config['timeout'] ?? 30))
                    ->post($this->buildSessionUrl($config, 'close-session')),
                fn () => Http::timeout((int) ($config['timeout'] ?? 30))
                    ->withToken((string) $config['api_token'])
                    ->post(rtrim((string) $config['api_base_url'], '/') . '/disconnect')
            );

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
            $response = $this->requestWithFallback(
                $config,
                fn () => Http::timeout((int) ($config['timeout'] ?? 30))
                    ->get($this->buildSessionUrl($config, 'check-connection-session')),
                fn () => Http::timeout((int) ($config['timeout'] ?? 30))
                    ->withToken((string) $config['api_token'])
                    ->get(rtrim((string) $config['api_base_url'], '/') . '/status')
            );

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

    protected function getSessionName(array $config): string
    {
        return trim((string) ($config['sender'] ?? 'default')) ?: 'default';
    }

    protected function buildSessionUrl(array $config, string $action): string
    {
        return rtrim((string) $config['api_base_url'], '/')
            . '/api/'
            . rawurlencode((string) $config['api_token'])
            . '/'
            . $action
            . '/'
            . rawurlencode($this->getSessionName($config));
    }

    protected function requestWithFallback(array $config, callable $sessionRequest, callable $legacyRequest): Response
    {
        try {
            $response = $sessionRequest();

            if ($response->status() !== 404) {
                return $response;
            }
        } catch (ConnectionException) {
            // Session-based endpoint not reachable (bridge runs legacy API only).
            // Fall through and attempt the legacy endpoint below.
        }

        return $legacyRequest();
    }

    protected function normalizeBridgeStatus(array $data): string
    {
        $status = $data['status'] ?? null;

        if ($status === true || $status === 'connected' || $status === 'CONNECTED') {
            return 'connected';
        }

        if ($status === 'waiting' || $status === 'WAITING' || ! empty($data['qrcode']) || ! empty($data['qr'])) {
            return 'waiting';
        }

        return 'disconnected';
    }
}

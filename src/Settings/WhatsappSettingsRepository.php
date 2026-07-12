<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class WhatsappSettingsRepository
{
    protected ?array $settings = null;

    protected const TABLE = 'whatsapp_bridge_settings';

    protected const CACHE_KEY = 'whatsapp_bridge_settings';

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    public function getProviderConfig(string $provider): array
    {
        $settings = $this->all();
        $providers = $settings['providers'] ?? [];

        return $providers[$provider] ?? [];
    }

    public function all(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $dbSettings = $this->getFromDb();

        $configDefaults = $this->getConfigDefaults();

        $merged = $this->mergeDefaults($configDefaults, $dbSettings);

        $this->settings = $merged;

        return $this->settings;
    }

    public function save(array $data): void
    {
        $existing = $this->getFromDb();
        $storedRecordId = $this->getStoredRecordId();

        $activeProvider = $data['active_provider'] ?? $existing['active_provider'] ?? 'bridge';
        $providers = $data['providers'] ?? $existing['providers'] ?? [];

        $this->populateProviderFromLegacyFields($providers, $activeProvider, $data, $existing);

        foreach ($providers as $providerKey => $providerConfig) {
            $providers[$providerKey] = $this->sanitizeProviderConfig(
                $providerKey,
                $providerConfig,
                $existing['providers'][$providerKey] ?? []
            );
        }

        // Sync legacy flat columns from bridge provider config for backward compatibility.
        // These columns still exist in the schema and should always reflect the current bridge settings.
        $bridgeFinal = $providers['bridge'] ?? [];

        $record = [
            'active_provider'      => $activeProvider,
            'provider_name'        => $data['provider_name'] ?? $existing['provider_name'] ?? 'default',
            'default_country_code' => $data['default_country_code'] ?? $existing['default_country_code'] ?? '20',
            'otp_enabled'          => $data['otp_enabled'] ?? $existing['otp_enabled'] ?? true,
            'messages_enabled'     => $data['messages_enabled'] ?? $existing['messages_enabled'] ?? true,
            'otp_template'         => $data['otp_template'] ?? $existing['otp_template'] ?? null,
            'timeout'              => (int) ($data['timeout'] ?? $existing['timeout'] ?? 30),
            'extra_settings'       => isset($data['extra_settings'])
                ? json_encode($data['extra_settings'])
                : ($existing['extra_settings'] ?? null),
            // Legacy flat columns kept in sync so DB viewers always show current bridge config.
            'api_base_url' => $bridgeFinal['api_base_url'] ?? null,
            'api_token'    => $bridgeFinal['api_token'] ?? null,   // stored encrypted by sanitizeProviderConfig
            'sender'       => $bridgeFinal['sender'] ?? null,
        ];

        $record['providers'] = json_encode($providers);

        if ($storedRecordId !== null) {
            DB::table(self::TABLE)->where('id', $storedRecordId)->update($record);
        } else {
            DB::table(self::TABLE)->insert($record);
        }

        $this->settings = null;
        Cache::forget(self::CACHE_KEY);
    }

    protected function populateProviderFromLegacyFields(array &$providers, string $activeProvider, array $data, array $existing): void
    {
        $legacyFields = ['api_base_url', 'api_token', 'sender'];

        foreach ($legacyFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
                $providers[$activeProvider][$field] = $data[$field];
            } elseif (! isset($providers[$activeProvider][$field]) && isset($existing[$field])) {
                $providers[$activeProvider][$field] = $existing[$field];
            }
        }
    }

    public function safeSettings(): array
    {
        $settings = $this->all();

        if (isset($settings['providers']) && is_array($settings['providers'])) {
            foreach ($settings['providers'] as $provider => &$config) {
                $config = $this->sanitizeProviderSensitiveFields($provider, $config);
            }
        }

        return $settings;
    }

    public function clearCache(): void
    {
        $this->settings = null;
        Cache::forget(self::CACHE_KEY);
    }

    protected function getFromDb(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        $record = Cache::remember(self::CACHE_KEY, 3600, function () {
            return DB::table(self::TABLE)->orderBy('id')->first();
        });

        if ($record === null) {
            return [];
        }

        $data = (array) $record;

        if (isset($data['providers']) && is_string($data['providers'])) {
            $data['providers'] = json_decode($data['providers'], true) ?? [];
        } else {
            $data['providers'] = $this->buildProvidersFromLegacy($data);
        }

        $data = $this->decryptProviderTokens($data);

        if (isset($data['extra_settings']) && is_string($data['extra_settings'])) {
            $data['extra_settings'] = json_decode($data['extra_settings'], true);
        }

        return $data;
    }

    protected function buildProvidersFromLegacy(array $data): array
    {
        $providers = [];

        $providers['bridge'] = [
            'api_base_url' => $data['api_base_url'] ?? null,
            'api_token' => $data['api_token'] ?? null,
            'sender' => $data['sender'] ?? null,
            'timeout' => $data['timeout'] ?? 30,
        ];

        $providers['twilio'] = [
            'account_sid' => null,
            'auth_token' => null,
            'from_number' => null,
            'timeout' => 30,
        ];

        $providers['meta'] = [
            'phone_number_id' => null,
            'access_token' => null,
            'business_account_id' => null,
            'verify_token' => null,
            'app_secret' => null,
            'timeout' => 30,
        ];

        return $providers;
    }

    protected function migrateLegacyFields(array &$record, array $existing, array $providers): void
    {
        unset($record['api_base_url'], $record['api_token'], $record['sender']);
    }

    protected function decryptProviderTokens(array $data): array
    {
        if (isset($data['providers']) && is_array($data['providers'])) {
            foreach ($data['providers'] as $provider => &$config) {
                $config = $this->decryptSensitiveFields($provider, $config);
            }
        }

        return $data;
    }

    protected function decryptSensitiveFields(string $provider, array $config): array
    {
        $sensitiveFields = match ($provider) {
            'bridge' => ['api_token'],
            'twilio' => ['auth_token'],
            'meta' => ['access_token', 'app_secret'],
            default => [],
        };

        foreach ($sensitiveFields as $field) {
            if (isset($config[$field]) && $config[$field] !== null) {
                try {
                    $config[$field . '_raw'] = Crypt::decryptString($config[$field]);
                    $config[$field] = $config[$field . '_raw'];
                } catch (\Throwable) {
                    unset($config[$field]);
                }
            }
        }

        return $config;
    }

    protected function sanitizeProviderConfig(string $provider, array $newConfig, array $existing): array
    {
        $sensitiveFields = match ($provider) {
            'bridge' => ['api_token'],
            'twilio' => ['auth_token'],
            'meta' => ['access_token', 'app_secret'],
            default => [],
        };

        foreach ($sensitiveFields as $field) {
            if (! isset($newConfig[$field]) || $newConfig[$field] === null || $newConfig[$field] === '') {
                $newConfig[$field] = $existing[$field] ?? null;
            } else {
                $newConfig[$field] = Crypt::encryptString($newConfig[$field]);
            }
        }

        return $newConfig;
    }

    protected function sanitizeProviderSensitiveFields(string $provider, array $config): array
    {
        $sensitiveFields = match ($provider) {
            'bridge' => ['api_token'],
            'twilio' => ['auth_token'],
            'meta' => ['access_token', 'app_secret'],
            default => [],
        };

        foreach ($sensitiveFields as $field) {
            if (isset($config[$field]) && $config[$field] !== null) {
                $token = (string) $config[$field];
                $config[$field] = $this->maskToken($token);
                $config['has_' . $field] = true;
            } else {
                $config[$field] = null;
                $config['has_' . $field] = false;
            }
        }

        return $config;
    }

    protected function getConfigDefaults(): array
    {
        return [
            'active_provider' => config('whatsapp-bridge-settings.active_provider', 'bridge'),
            'provider_name' => 'default',
            'default_country_code' => config('whatsapp-bridge-settings.default_country_code', '20'),
            'otp_enabled' => config('whatsapp-bridge-settings.otp_enabled', true),
            'messages_enabled' => config('whatsapp-bridge-settings.messages_enabled', true),
            'otp_template' => config('whatsapp-bridge-settings.otp_template'),
            'timeout' => (int) config('whatsapp-bridge-settings.timeout', 30),
            'providers' => [
                'bridge' => [
                    'api_base_url' => config('whatsapp-bridge-settings.providers.bridge.api_base_url'),
                    'api_token' => config('whatsapp-bridge-settings.providers.bridge.api_token'),
                    'sender' => config('whatsapp-bridge-settings.providers.bridge.sender'),
                    'timeout' => (int) config('whatsapp-bridge-settings.providers.bridge.timeout', 30),
                ],
                'twilio' => [
                    'account_sid' => config('whatsapp-bridge-settings.providers.twilio.account_sid'),
                    'auth_token' => config('whatsapp-bridge-settings.providers.twilio.auth_token'),
                    'from_number' => config('whatsapp-bridge-settings.providers.twilio.from_number'),
                    'timeout' => (int) config('whatsapp-bridge-settings.providers.twilio.timeout', 30),
                ],
                'meta' => [
                    'phone_number_id' => config('whatsapp-bridge-settings.providers.meta.phone_number_id'),
                    'access_token' => config('whatsapp-bridge-settings.providers.meta.access_token'),
                    'business_account_id' => config('whatsapp-bridge-settings.providers.meta.business_account_id'),
                    'verify_token' => config('whatsapp-bridge-settings.providers.meta.verify_token'),
                    'app_secret' => config('whatsapp-bridge-settings.providers.meta.app_secret'),
                    'timeout' => (int) config('whatsapp-bridge-settings.providers.meta.timeout', 30),
                ],
            ],
        ];
    }

    protected function mergeDefaults(array $defaults, array $dbSettings): array
    {
        $merged = $defaults;

        foreach ($dbSettings as $key => $value) {
            if ($value === null) {
                continue;
            }

            if ($key === 'providers' && is_array($value)) {
                foreach ($value as $provider => $config) {
                    if (! isset($merged['providers'][$provider])) {
                        $merged['providers'][$provider] = $config;
                    } else {
                        $merged['providers'][$provider] = array_merge(
                            $merged['providers'][$provider],
                            $config
                        );
                    }
                }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    protected function tableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable(self::TABLE);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function getStoredRecordId(): ?int
    {
        if (! $this->tableExists()) {
            return null;
        }

        $id = DB::table(self::TABLE)->orderBy('id')->value('id');

        return $id !== null ? (int) $id : null;
    }

    protected function maskToken(string $token): string
    {
        $len = strlen($token);

        if ($len <= 8) {
            return str_repeat('•', $len);
        }

        return substr($token, 0, 4) . str_repeat('•', $len - 8) . substr($token, -4);
    }
}

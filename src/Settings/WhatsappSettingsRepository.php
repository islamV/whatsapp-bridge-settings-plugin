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

    public function all(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $dbSettings = $this->getFromDb();

        $configDefaults = $this->getConfigDefaults();

        $merged = array_merge($configDefaults, $dbSettings);

        if (isset($merged['api_token_raw'])) {
            unset($merged['api_token_raw']);
        }

        $this->settings = $merged;

        return $this->settings;
    }

    public function save(array $data): void
    {
        $token = $data['api_token'] ?? null;

        $existing = $this->getFromDb();

        if ($token === null || $token === '') {
            $token = $existing['api_token'] ?? null;
        } else {
            $token = Crypt::encryptString($token);
        }

        $record = [
            'provider_name' => $data['provider_name'] ?? $existing['provider_name'] ?? 'default',
            'api_base_url' => $data['api_base_url'] ?? $existing['api_base_url'] ?? null,
            'api_token' => $token,
            'sender' => $data['sender'] ?? $existing['sender'] ?? null,
            'default_country_code' => $data['default_country_code'] ?? $existing['default_country_code'] ?? '20',
            'otp_enabled' => $data['otp_enabled'] ?? $existing['otp_enabled'] ?? true,
            'messages_enabled' => $data['messages_enabled'] ?? $existing['messages_enabled'] ?? true,
            'otp_template' => $data['otp_template'] ?? $existing['otp_template'] ?? null,
            'timeout' => (int) ($data['timeout'] ?? $existing['timeout'] ?? 30),
            'extra_settings' => isset($data['extra_settings'])
                ? json_encode($data['extra_settings'])
                : ($existing['extra_settings'] ?? null),
        ];

        $count = DB::table(self::TABLE)->count();

        if ($count > 0) {
            DB::table(self::TABLE)->where('id', 1)->update($record);
        } else {
            DB::table(self::TABLE)->insert($record);
        }

        $this->settings = null;
        Cache::forget(self::CACHE_KEY);
    }

    public function safeSettings(): array
    {
        $settings = $this->all();

        if (isset($settings['api_token']) && $settings['api_token'] !== null) {
            $token = (string) $settings['api_token'];
            $settings['api_token'] = $this->maskToken($token);
            $settings['has_token'] = true;
        } else {
            $settings['api_token'] = null;
            $settings['has_token'] = false;
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
            return DB::table(self::TABLE)->where('id', 1)->first();
        });

        if ($record === null) {
            return [];
        }

        $data = (array) $record;

        if (isset($data['api_token']) && $data['api_token'] !== null) {
            try {
                $data['api_token_raw'] = Crypt::decryptString($data['api_token']);
                $data['api_token'] = $data['api_token_raw'];
            } catch (\Throwable) {
                unset($data['api_token']);
            }
        }

        if (isset($data['extra_settings']) && is_string($data['extra_settings'])) {
            $data['extra_settings'] = json_decode($data['extra_settings'], true);
        }

        return $data;
    }

    protected function getConfigDefaults(): array
    {
        return [
            'provider_name' => config('whatsapp-bridge-settings.provider', 'default'),
            'api_base_url' => config('whatsapp-bridge-settings.api_base_url'),
            'api_token' => config('whatsapp-bridge-settings.api_token'),
            'sender' => config('whatsapp-bridge-settings.sender'),
            'default_country_code' => config('whatsapp-bridge-settings.default_country_code', '20'),
            'otp_enabled' => config('whatsapp-bridge-settings.otp_enabled', true),
            'messages_enabled' => config('whatsapp-bridge-settings.messages_enabled', true),
            'otp_template' => config('whatsapp-bridge-settings.otp_template'),
            'timeout' => (int) config('whatsapp-bridge-settings.timeout', 30),
        ];
    }

    protected function tableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable(self::TABLE);
        } catch (\Throwable) {
            return false;
        }
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

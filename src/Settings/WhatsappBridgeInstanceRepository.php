<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Settings;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class WhatsappBridgeInstanceRepository
{
    protected const TABLE = 'whatsapp_bridge_instances';

    public function all(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return DB::table(self::TABLE)->orderByDesc('is_default')->orderBy('name')->get()->map(fn ($row): array => $this->decrypt((array) $row))->all();
    }

    public function find(string|int $id): ?array
    {
        if (! $this->tableExists()) {
            return null;
        }

        $row = DB::table(self::TABLE)->where('id', $id)->first();

        return $row ? $this->decrypt((array) $row) : null;
    }

    public function default(): ?array
    {
        $instance = collect($this->all())->first(fn (array $row): bool => (bool) $row['enabled'] && (bool) $row['is_default']);

        return $instance ?? collect($this->all())->first(fn (array $row): bool => (bool) $row['enabled']);
    }

    public function save(array $data, string|int|null $id = null): array
    {
        $existing = $id !== null ? $this->find($id) : null;
        $record = [
            'name' => trim((string) ($data['name'] ?? $existing['name'] ?? 'Default')),
            'api_base_url' => rtrim(trim((string) ($data['api_base_url'] ?? $existing['api_base_url'] ?? '')), '/'),
            'session_name' => trim((string) ($data['session_name'] ?? $existing['session_name'] ?? 'default')),
            'sender' => $data['sender'] ?? $existing['sender'] ?? null,
            'timeout' => (int) ($data['timeout'] ?? $existing['timeout'] ?? 30),
            'enabled' => (bool) ($data['enabled'] ?? $existing['enabled'] ?? true),
            'is_default' => (bool) ($data['is_default'] ?? $existing['is_default'] ?? false),
            'updated_at' => now(),
        ];

        $token = $data['api_token'] ?? null;
        if ($token !== null && $token !== '') {
            $record['api_token'] = Crypt::encryptString((string) $token);
        } elseif ($existing !== null) {
            $record['api_token'] = Crypt::encryptString((string) ($existing['api_token'] ?? ''));
        } else {
            $record['api_token'] = null;
        }

        if ($record['is_default']) {
            DB::table(self::TABLE)->update(['is_default' => false]);
        }

        if ($id !== null && $existing !== null) {
            DB::table(self::TABLE)->where('id', $id)->update($record);

            return $this->find($id);
        }

        $record['created_at'] = now();
        $newId = DB::table(self::TABLE)->insertGetId($record);
        if (! $this->default()) {
            DB::table(self::TABLE)->where('id', $newId)->update(['is_default' => true]);
        }

        return $this->find($newId);
    }

    public function delete(string|int $id): bool
    {
        $row = $this->find($id);
        if ($row === null) {
            return false;
        }

        $count = DB::table(self::TABLE)->count();
        if ($count <= 1) {
            return false;
        }

        $deleted = DB::table(self::TABLE)->where('id', $id)->delete() > 0;
        if ($deleted && $row['is_default']) {
            $next = DB::table(self::TABLE)->orderBy('id')->first();
            if ($next) {
                DB::table(self::TABLE)->where('id', $next->id)->update(['is_default' => true]);
            }
        }

        return $deleted;
    }

    protected function decrypt(array $row): array
    {
        if (! empty($row['api_token'])) {
            try {
                $row['api_token'] = Crypt::decryptString($row['api_token']);
            } catch (\Throwable) {
                // Keep legacy plaintext tokens readable during migration.
            }
        }

        return $row;
    }

    protected function tableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable(self::TABLE);
        } catch (\Throwable) {
            return false;
        }
    }
}

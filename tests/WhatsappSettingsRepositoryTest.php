<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Orchestra\Testbench\TestCase;

class WhatsappSettingsRepositoryTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsappBridgeSettingsPluginServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTable();
    }

    protected function createTable(): void
    {
        DB::statement('
            CREATE TABLE whatsapp_bridge_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                provider_name VARCHAR(255) DEFAULT "default",
                api_base_url VARCHAR(255) NULL,
                api_token TEXT NULL,
                sender VARCHAR(255) NULL,
                default_country_code VARCHAR(10) DEFAULT "20",
                otp_enabled TINYINT(1) DEFAULT 1,
                messages_enabled TINYINT(1) DEFAULT 1,
                otp_template TEXT NULL,
                timeout INTEGER DEFAULT 30,
                extra_settings TEXT NULL,
                active_provider VARCHAR(255) DEFAULT "bridge",
                providers TEXT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )
        ');
    }

    public function test_returns_config_defaults_when_no_db_record(): void
    {
        Config::set('whatsapp-bridge-settings.providers.bridge.api_base_url', 'https://test.example.com');
        Config::set('whatsapp-bridge-settings.timeout', 60);

        $repository = $this->app->make(WhatsappSettingsRepository::class);

        $this->assertEquals('https://test.example.com', $repository->getProviderConfig('bridge')['api_base_url']);
        $this->assertEquals(60, $repository->get('timeout'));
    }

    public function test_saves_and_retrieves_settings(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);

        $repository->save([
            'api_base_url' => 'https://api.test.com',
            'api_token' => 'test-token-123',
            'sender' => 'test-sender',
            'default_country_code' => '20',
            'otp_enabled' => true,
            'messages_enabled' => true,
            'timeout' => 45,
        ]);

        $repository->clearCache();

        $config = $repository->getProviderConfig('bridge');
        $this->assertEquals('https://api.test.com', $config['api_base_url']);
        $this->assertEquals('test-token-123', $config['api_token']);
        $this->assertEquals('test-sender', $config['sender']);
        $this->assertEquals(45, $repository->get('timeout'));
    }

    public function test_api_token_is_encrypted_in_database(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);

        $repository->save([
            'api_token' => 'super-secret-token',
        ]);

        $record = DB::table('whatsapp_bridge_settings')->first();

        $this->assertNotNull($record);
        $this->assertNotNull($record->providers);

        $providers = json_decode($record->providers, true);
        $this->assertNotNull($providers['bridge']['api_token']);
        $this->assertNotEquals('super-secret-token', $providers['bridge']['api_token']);

        $decrypted = Crypt::decryptString($providers['bridge']['api_token']);
        $this->assertEquals('super-secret-token', $decrypted);
    }

    public function test_preserves_existing_token_when_token_field_is_empty(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);

        $repository->save(['api_token' => 'existing-token']);

        $repository->clearCache();

        $repository->save([
            'api_base_url' => 'https://updated.example.com',
            'api_token' => '',
        ]);

        $repository->clearCache();

        $config = $repository->getProviderConfig('bridge');
        $this->assertEquals('existing-token', $config['api_token']);
    }

    public function test_safe_settings_masks_token(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);

        $repository->save(['api_token' => 'abcdefghijklmnop']);

        $repository->clearCache();

        $safe = $repository->safeSettings();

        $this->assertStringContainsString('abcd', $safe['providers']['bridge']['api_token']);
        $this->assertStringContainsString('mnop', $safe['providers']['bridge']['api_token']);
        $this->assertStringNotContainsString('efghijkl', $safe['providers']['bridge']['api_token']);
        $this->assertTrue($safe['providers']['bridge']['has_api_token']);
    }

    public function test_returns_all_settings_as_array(): void
    {
        Config::set('whatsapp-bridge-settings.default_country_code', '44');
        Config::set('whatsapp-bridge-settings.otp_enabled', false);

        $repository = $this->app->make(WhatsappSettingsRepository::class);

        $all = $repository->all();

        $this->assertIsArray($all);
        $this->assertArrayHasKey('default_country_code', $all);
        $this->assertArrayHasKey('timeout', $all);
        $this->assertArrayNotHasKey('api_token_raw', $all);
    }
}

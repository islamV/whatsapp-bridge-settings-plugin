<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Illuminate\Support\Facades\DB;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Services\MetaWhatsapp;
use Islamv\WhatsappBridgeSettingsPlugin\Services\TwilioWhatsapp;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappBridge;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Orchestra\Testbench\TestCase;

class ProviderSelectionTest extends TestCase
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

    public function test_default_provider_is_bridge(): void
    {
        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertInstanceOf(WhatsappBridge::class, $provider);
    }

    public function test_resolves_bridge_when_active_provider_is_bridge(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save(['active_provider' => 'bridge']);
        $repository->clearCache();

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertInstanceOf(WhatsappBridge::class, $provider);
    }

    public function test_resolves_meta_when_active_provider_is_meta(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save(['active_provider' => 'meta']);
        $repository->clearCache();

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertInstanceOf(MetaWhatsapp::class, $provider);
    }

    public function test_resolves_twilio_when_active_provider_is_twilio(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save(['active_provider' => 'twilio']);
        $repository->clearCache();

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertInstanceOf(TwilioWhatsapp::class, $provider);
    }

    public function test_resolves_bridge_for_unknown_provider(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save(['active_provider' => 'unknown_provider']);
        $repository->clearCache();

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertInstanceOf(WhatsappBridge::class, $provider);
    }

    public function test_named_binding_resolves_correct_provider(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save(['active_provider' => 'twilio']);
        $repository->clearCache();

        $whatsapp = $this->app->make('whatsapp-bridge-settings.whatsapp');

        $this->assertInstanceOf(TwilioWhatsapp::class, $whatsapp);
    }

    public function test_otp_sender_uses_active_provider(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save(['active_provider' => 'meta']);
        $repository->clearCache();

        $otpSender = $this->app->make(\Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappOtpSender::class);

        $reflection = new \ReflectionProperty($otpSender, 'whatsapp');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(MetaWhatsapp::class, $reflection->getValue($otpSender));
    }
}

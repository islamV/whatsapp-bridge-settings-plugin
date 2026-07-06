<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Services\MetaWhatsapp;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Orchestra\Testbench\TestCase;

class MetaWhatsappTest extends TestCase
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

    protected function configureMetaProvider(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save([
            'active_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'phone_number_id' => '123456789',
                    'access_token' => 'meta-access-token',
                    'business_account_id' => 'bus-123',
                    'verify_token' => 'verify-123',
                    'app_secret' => 'app-secret-123',
                    'timeout' => 5,
                ],
            ],
        ]);
        $repository->clearCache();
    }

    public function test_resolves_as_meta_provider(): void
    {
        $this->configureMetaProvider();

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertInstanceOf(MetaWhatsapp::class, $provider);
    }

    public function test_send_message_makes_http_request_and_returns_true_on_success(): void
    {
        $this->configureMetaProvider();

        Http::fake([
            'graph.facebook.com/*' => Http::response([], 200),
        ]);

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $result = $provider->sendMessage('201000000000', 'Hello World');

        $this->assertTrue($result);
    }

    public function test_send_message_returns_false_on_http_error(): void
    {
        $this->configureMetaProvider();

        Http::fake([
            'graph.facebook.com/*' => Http::response([], 400),
        ]);

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $result = $provider->sendMessage('201000000000', 'Hello World');

        $this->assertFalse($result);
    }

    public function test_send_message_returns_false_when_not_configured(): void
    {
        $provider = $this->app->make(WhatsappProviderInterface::class);

        $result = $provider->sendMessage('201000000000', 'Hello World');

        $this->assertFalse($result);
    }

    public function test_send_otp_fills_template_and_calls_send_message(): void
    {
        $this->configureMetaProvider();

        Http::fake(function ($request) {
            return Http::response([], 200);
        });

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $result = $provider->sendOtp('201000000000', '987654');

        $this->assertTrue($result);
    }

    public function test_send_otp_returns_false_when_otp_disabled(): void
    {
        $this->configureMetaProvider();

        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save(['otp_enabled' => false]);
        $repository->clearCache();

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $result = $provider->sendOtp('201000000000', '123456');

        $this->assertFalse($result);
    }

    public function test_get_connection_status_returns_connected(): void
    {
        $this->configureMetaProvider();

        Http::fake([
            'graph.facebook.com/*' => Http::response([], 200),
        ]);

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertEquals('connected', $provider->getConnectionStatus());
    }

    public function test_get_connection_status_returns_disconnected_when_not_configured(): void
    {
        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertEquals('disconnected', $provider->getConnectionStatus());
    }

    public function test_generate_qr_code_returns_null(): void
    {
        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertNull($provider->generateQrCode());
    }

    public function test_disconnect_returns_true(): void
    {
        $this->configureMetaProvider();

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertTrue($provider->disconnect());
    }

    public function test_get_connected_phone_returns_phone_number_id(): void
    {
        $this->configureMetaProvider();

        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertEquals('123456789', $provider->getConnectedPhone());
    }

    public function test_send_message_sends_correct_payload(): void
    {
        $this->configureMetaProvider();

        $captured = null;

        Http::fake(function ($request) use (&$captured) {
            $captured = $request;

            return Http::response([], 200);
        });

        $provider = $this->app->make(WhatsappProviderInterface::class);
        $provider->sendMessage('201000000000', 'Test message body');

        $this->assertNotNull($captured);

        $body = $captured->data();
        $this->assertEquals('whatsapp', $body['messaging_product']);
        $this->assertEquals('text', $body['type']);
        $this->assertEquals('Test message body', $body['text']['body']);
    }
}

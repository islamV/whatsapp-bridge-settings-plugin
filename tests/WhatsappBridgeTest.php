<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Orchestra\Testbench\TestCase;

class WhatsappBridgeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsappBridgeSettingsPluginServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
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
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )
        ');

        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save([
            'api_base_url' => 'https://whatsapp-api.test',
            'api_token' => 'test-token',
            'sender' => 'test-sender',
            'timeout' => 5,
        ]);
        $repository->clearCache();
    }

    public function test_send_message_makes_http_request_and_returns_true_on_success(): void
    {
        Config::set('whatsapp-bridge-settings.api_base_url', 'https://whatsapp-api.test');
        Config::set('whatsapp-bridge-settings.api_token', 'test-token');

        Http::fake([
            'whatsapp-api.test/*' => Http::response(['status' => 'sent'], 200),
        ]);

        $bridge = $this->app->make(\Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface::class);

        $result = $bridge->sendMessage('201000000000', 'Hello World');

        $this->assertTrue($result);
    }

    public function test_send_message_returns_false_on_http_error(): void
    {
        Http::fake([
            'whatsapp-api.test/*' => Http::response([], 500),
        ]);

        $bridge = $this->app->make(\Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface::class);

        $result = $bridge->sendMessage('201000000000', 'Hello World');

        $this->assertFalse($result);
    }

    public function test_send_message_returns_false_on_exception(): void
    {
        Http::fake([
            'whatsapp-api.test/*' => function () {
                throw new \Exception('Connection refused');
            },
        ]);

        $bridge = $this->app->make(\Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface::class);

        $result = $bridge->sendMessage('201000000000', 'Hello World');

        $this->assertFalse($result);
    }

    public function test_send_message_returns_false_when_not_configured(): void
    {
        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save([
            'api_base_url' => '',
            'api_token' => '',
        ]);
        $repository->clearCache();

        $bridge = $this->app->make(\Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface::class);

        $result = $bridge->sendMessage('201000000000', 'Hello World');

        $this->assertFalse($result);
    }

    public function test_send_otp_fills_template_and_calls_send_message(): void
    {
        Config::set('whatsapp-bridge-settings.api_base_url', 'https://whatsapp-api.test');
        Config::set('whatsapp-bridge-settings.api_token', 'test-token');
        Config::set('whatsapp-bridge-settings.otp_template', 'Your code is: {otp}');

        $calledWith = null;

        Http::fake(function ($request) use (&$calledWith) {
            $calledWith = $request->data();

            return Http::response([], 200);
        });

        $bridge = $this->app->make(\Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface::class);

        $result = $bridge->sendOtp('201000000000', '987654');

        $this->assertTrue($result);
        $this->assertNotNull($calledWith);
        $this->assertStringContainsString('Your code is: 987654', $calledWith['text']);
    }

    public function test_send_otp_returns_false_when_otp_disabled(): void
    {
        Config::set('whatsapp-bridge-settings.otp_enabled', false);

        $repository = $this->app->make(WhatsappSettingsRepository::class);
        $repository->save(['otp_enabled' => false]);
        $repository->clearCache();

        $bridge = $this->app->make(\Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface::class);

        $result = $bridge->sendOtp('201000000000', '123456');

        $this->assertFalse($result);
    }
}

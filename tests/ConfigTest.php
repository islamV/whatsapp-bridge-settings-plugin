<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Orchestra\Testbench\TestCase;

class ConfigTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsappBridgeSettingsPluginServiceProvider::class,
        ];
    }

    public function test_config_is_mergeable(): void
    {
        $this->assertIsArray(config('whatsapp-bridge-settings'));
    }

    public function test_config_has_defaults(): void
    {
        $this->assertEquals('default', config('whatsapp-bridge-settings.provider'));
        $this->assertEquals('20', config('whatsapp-bridge-settings.default_country_code'));
        $this->assertEquals(30, config('whatsapp-bridge-settings.timeout'));
        $this->assertTrue(config('whatsapp-bridge-settings.otp_enabled'));
        $this->assertTrue(config('whatsapp-bridge-settings.messages_enabled'));
        $this->assertEquals(
            'Your verification code is: {otp}',
            config('whatsapp-bridge-settings.otp_template')
        );
    }

    public function test_config_supports_env_override(): void
    {
        $this->app['config']->set('whatsapp-bridge-settings.api_base_url', 'https://env-test.example.com');
        $this->app['config']->set('whatsapp-bridge-settings.timeout', 99);

        $this->assertEquals('https://env-test.example.com', config('whatsapp-bridge-settings.api_base_url'));
        $this->assertEquals(99, config('whatsapp-bridge-settings.timeout'));
    }
}

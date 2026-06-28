<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappBridge;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappOtpSender;
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsappBridgeSettingsPluginServiceProvider::class,
        ];
    }

    public function test_service_provider_binds_whatsapp_provider_interface(): void
    {
        $provider = $this->app->make(WhatsappProviderInterface::class);

        $this->assertInstanceOf(WhatsappBridge::class, $provider);
    }

    public function test_service_provider_registers_named_binding(): void
    {
        $whatsapp = $this->app->make('whatsapp-bridge-settings.whatsapp');

        $this->assertInstanceOf(WhatsappProviderInterface::class, $whatsapp);
    }

    public function test_service_provider_registers_otp_sender(): void
    {
        $otpSender = $this->app->make(WhatsappOtpSender::class);

        $this->assertInstanceOf(WhatsappOtpSender::class, $otpSender);
    }

    public function test_otp_sender_receives_whatsapp_provider(): void
    {
        $otpSender = $this->app->make(WhatsappOtpSender::class);

        $reflection = new \ReflectionProperty($otpSender, 'whatsapp');
        $reflection->setAccessible(true);

        $this->assertInstanceOf(WhatsappProviderInterface::class, $reflection->getValue($otpSender));
    }
}

<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Tests;

use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappOtpSender;
use Islamv\WhatsappBridgeSettingsPlugin\WhatsappBridgeSettingsPluginServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase;

class WhatsappOtpSenderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsappBridgeSettingsPluginServiceProvider::class,
        ];
    }

    public function test_send_otp_calls_provider_send_otp(): void
    {
        $provider = Mockery::mock(WhatsappProviderInterface::class);
        $provider->shouldReceive('sendOtp')
            ->once()
            ->with('201000000000', '123456', [])
            ->andReturn(true);

        $sender = new WhatsappOtpSender($provider);

        $result = $sender->send('201000000000', '123456');

        $this->assertTrue($result);
    }

    public function test_send_otp_passes_options(): void
    {
        $provider = Mockery::mock(WhatsappProviderInterface::class);
        $provider->shouldReceive('sendOtp')
            ->once()
            ->with('201000000000', '654321', ['priority' => 'high'])
            ->andReturn(true);

        $sender = new WhatsappOtpSender($provider);

        $result = $sender->send('201000000000', '654321', ['priority' => 'high']);

        $this->assertTrue($result);
    }

    public function test_send_otp_returns_false_when_provider_fails(): void
    {
        $provider = Mockery::mock(WhatsappProviderInterface::class);
        $provider->shouldReceive('sendOtp')
            ->once()
            ->andReturn(false);

        $sender = new WhatsappOtpSender($provider);

        $result = $sender->send('201000000000', '000000');

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

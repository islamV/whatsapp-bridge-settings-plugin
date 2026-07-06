<?php

namespace Islamv\WhatsappBridgeSettingsPlugin;

use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Services\MetaWhatsapp;
use Islamv\WhatsappBridgeSettingsPlugin\Services\TwilioWhatsapp;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappBridge;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappOtpSender;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WhatsappBridgeSettingsPluginServiceProvider extends PackageServiceProvider
{
    public static string $name = 'whatsapp-bridge-settings';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile('whatsapp-bridge-settings')
            ->hasMigration('create_whatsapp_bridge_settings_table')
            ->hasViews()
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WhatsappSettingsRepository::class);

        $this->app->singleton(WhatsappProviderInterface::class, function ($app) {
            $settings = $app->make(WhatsappSettingsRepository::class);
            $provider = $settings->get('active_provider', 'bridge');

            return match ($provider) {
                'meta' => $app->make(MetaWhatsapp::class),
                'twilio' => $app->make(TwilioWhatsapp::class),
                default => $app->make(WhatsappBridge::class),
            };
        });

        $this->app->singleton('whatsapp-bridge-settings.whatsapp', function ($app) {
            return $app->make(WhatsappProviderInterface::class);
        });

        $this->app->singleton(WhatsappOtpSender::class, function ($app) {
            return new WhatsappOtpSender(
                $app->make(WhatsappProviderInterface::class)
            );
        });
    }
}

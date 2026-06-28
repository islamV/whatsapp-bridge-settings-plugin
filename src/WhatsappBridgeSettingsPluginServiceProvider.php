<?php

namespace Islamv\WhatsappBridgeSettingsPlugin;

use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappBridge;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappOtpSender;
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
        $this->app->singleton(WhatsappProviderInterface::class, WhatsappBridge::class);

        $this->app->singleton('whatsapp-bridge-settings.whatsapp', function () {
            return $this->app->make(WhatsappProviderInterface::class);
        });

        $this->app->singleton(WhatsappOtpSender::class, function () {
            return new WhatsappOtpSender(
                $this->app->make(WhatsappProviderInterface::class)
            );
        });
    }
}

<?php

namespace Islamv\WhatsappBridgeSettingsPlugin;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Islamv\WhatsappBridgeSettingsPlugin\Filament\Pages\WhatsappSettingsPage;

class WhatsappBridgeSettingsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'whatsapp-bridge-settings';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                WhatsappSettingsPage::class,
            ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}

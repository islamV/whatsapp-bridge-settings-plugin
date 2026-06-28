<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WhatsappProvider: string implements HasColor, HasLabel
{
    case Bridge = 'bridge';
    case Meta = 'meta';
    case Twilio = 'twilio';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bridge => __('whatsapp-bridge-settings::messages.providers.bridge.label'),
            self::Meta => __('whatsapp-bridge-settings::messages.providers.meta.label'),
            self::Twilio => __('whatsapp-bridge-settings::messages.providers.twilio.label'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Bridge => 'success',
            self::Meta => 'info',
            self::Twilio => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Bridge => __('whatsapp-bridge-settings::messages.providers.bridge.description'),
            self::Meta => __('whatsapp-bridge-settings::messages.providers.meta.description'),
            self::Twilio => __('whatsapp-bridge-settings::messages.providers.twilio.description'),
        };
    }

    public function getIconName(): string
    {
        return match ($this) {
            self::Bridge => 'link',
            self::Meta => 'globe-alt',
            self::Twilio => 'cloud',
        };
    }
}

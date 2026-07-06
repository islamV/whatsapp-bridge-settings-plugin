<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Concerns;

use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

trait HasLogChannel
{
    protected function logChannel(): string
    {
        $settings = app(WhatsappSettingsRepository::class);

        return $settings->get('log_channel') ?? config('logging.default', 'stack');
    }
}

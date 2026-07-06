<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Concerns;

use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

trait HandlesOtpMessages
{
    protected function buildOtpMessage(string $otp): ?string
    {
        $settings = app(WhatsappSettingsRepository::class);

        if (! $settings->get('otp_enabled', true)) {
            return null;
        }

        $template = $settings->get('otp_template', 'Your verification code is: {otp}');

        return str_replace('{otp}', $otp, (string) $template);
    }
}

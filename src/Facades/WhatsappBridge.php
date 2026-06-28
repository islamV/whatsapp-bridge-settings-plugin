<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool sendMessage(string $to, string $message, array $options = [])
 * @method static bool sendOtp(string $to, string $otp, array $options = [])
 *
 * @see \Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface
 */
class WhatsappBridge extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'whatsapp-bridge-settings.whatsapp';
    }
}

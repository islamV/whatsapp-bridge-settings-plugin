<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Services;

use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;

class WhatsappOtpSender
{
    public function __construct(
        protected WhatsappProviderInterface $whatsapp
    ) {}

    public function send(string $phone, string $otp, array $options = []): bool
    {
        return $this->whatsapp->sendOtp($phone, $otp, $options);
    }
}

<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Contracts;

interface WhatsappProviderInterface
{
    public function sendMessage(string $to, string $message, array $options = []): bool;

    public function sendOtp(string $to, string $otp, array $options = []): bool;
}

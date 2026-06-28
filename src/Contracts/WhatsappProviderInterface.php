<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Contracts;

interface WhatsappProviderInterface
{
    public function sendMessage(string $to, string $message, array $options = []): bool;

    public function sendOtp(string $to, string $otp, array $options = []): bool;

    public function getConnectionStatus(): string;

    public function generateQrCode(): ?string;

    public function disconnect(): bool;

    public function getConnectedPhone(): ?string;
}

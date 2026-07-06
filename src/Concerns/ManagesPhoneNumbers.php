<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Concerns;

trait ManagesPhoneNumbers
{
    protected function normalizePhone(string $phone, string $defaultCountryCode): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($cleaned === '') {
            return $phone;
        }

        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }

        if (strlen($cleaned) <= 10 && ! str_starts_with($cleaned, '00')) {
            $cleaned = ltrim($cleaned, '0');
            $cleaned = $defaultCountryCode . $cleaned;
        }

        return $cleaned;
    }

    protected function maskPhone(string $phone): string
    {
        $len = strlen($phone);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($phone, 0, 4) . str_repeat('*', $len - 8) . substr($phone, -4);
    }
}

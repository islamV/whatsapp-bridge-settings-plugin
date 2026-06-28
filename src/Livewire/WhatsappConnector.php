<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Livewire;

use Livewire\Component;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Enums\WhatsappProvider;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

class WhatsappConnector extends Component
{
    public string $activeTab = 'general';

    public string $activeProvider = 'bridge';

    public string $status = 'disconnected';

    public ?string $qrCode = null;

    public ?string $connectedPhone = null;

    public string $testPhone = '';

    public string $testMessage = '';

    public ?string $sendResult = null;

    public bool $sendSuccess = false;

    public bool $isSending = false;

    public int $countdown = 0;

    public ?array $bridgeConfig = null;

    public ?array $metaConfig = null;

    public ?array $twilioConfig = null;

    public bool $otpEnabled = true;

    public bool $messagesEnabled = true;

    public string $otpTemplate = 'Your verification code is: {otp}';

    public function mount(): void
    {
        $this->loadSettings();
        $this->checkStatus();
    }

    public function loadSettings(): void
    {
        $repository = app(WhatsappSettingsRepository::class);
        $settings = $repository->safeSettings();

        $this->activeProvider = $settings['active_provider'] ?? 'bridge';
        $this->otpEnabled = $settings['otp_enabled'] ?? true;
        $this->messagesEnabled = $settings['messages_enabled'] ?? true;
        $this->otpTemplate = $settings['otp_template'] ?? 'Your verification code is: {otp}';

        $providers = $settings['providers'] ?? [];
        $this->bridgeConfig = $providers['bridge'] ?? [
            'api_base_url' => '',
            'api_token' => '',
            'sender' => '',
            'timeout' => 30,
        ];
        $this->metaConfig = $providers['meta'] ?? [
            'phone_number_id' => '',
            'access_token' => '',
            'business_account_id' => '',
            'verify_token' => '',
            'app_secret' => '',
            'timeout' => 30,
        ];
        $this->twilioConfig = $providers['twilio'] ?? [
            'account_sid' => '',
            'auth_token' => '',
            'from_number' => '',
            'timeout' => 30,
        ];
    }

    public function getProviders(): array
    {
        return collect(WhatsappProvider::cases())
            ->mapWithKeys(fn (WhatsappProvider $provider) => [
                $provider->value => [
                    'label' => $provider->getLabel(),
                    'description' => $provider->getDescription(),
                    'icon' => $provider->getIconName(),
                    'color' => $provider->getColor(),
                ],
            ])
            ->toArray();
    }

    public function checkStatus(): void
    {
        $whatsapp = app(WhatsappProviderInterface::class);
        $this->status = $whatsapp->getConnectionStatus();

        if ($this->status === 'connected') {
            $this->connectedPhone = $whatsapp->getConnectedPhone();
            $this->qrCode = null;
            $this->countdown = 0;
        } else {
            $this->connectedPhone = null;
            $this->qrCode = null;
            $this->countdown = 0;
        }
    }

    public function generateQr(): void
    {
        $whatsapp = app(WhatsappProviderInterface::class);
        $this->qrCode = $whatsapp->generateQrCode();
        $this->status = 'waiting';
        $this->countdown = 60;
        $this->sendResult = null;
    }

    public function disconnect(): void
    {
        $whatsapp = app(WhatsappProviderInterface::class);
        $whatsapp->disconnect();

        $this->status = 'disconnected';
        $this->qrCode = null;
        $this->connectedPhone = null;
        $this->countdown = 0;
        $this->sendResult = null;
    }

    public function sendTestMessage(): void
    {
        $this->isSending = true;
        $this->sendResult = null;

        $whatsapp = app(WhatsappProviderInterface::class);
        $success = $whatsapp->sendMessage($this->testPhone, $this->testMessage);

        $this->sendSuccess = $success;
        $this->sendResult = $success
            ? __('whatsapp-bridge-settings::messages.notifications.test_sent')
            : __('whatsapp-bridge-settings::messages.notifications.test_failed');
        $this->isSending = false;
    }

    public function saveGeneral(): void
    {
        $this->saveSettings();
    }

    public function saveBridge(): void
    {
        $this->saveSettings();
    }

    public function saveMeta(): void
    {
        $this->saveSettings();
    }

    public function saveTwilio(): void
    {
        $this->saveSettings();
    }

    protected function saveSettings(): void
    {
        $repository = app(WhatsappSettingsRepository::class);

        $repository->save([
            'active_provider' => $this->activeProvider,
            'otp_enabled' => $this->otpEnabled,
            'messages_enabled' => $this->messagesEnabled,
            'otp_template' => $this->otpTemplate,
            'providers' => [
                'bridge' => $this->bridgeConfig,
                'meta' => $this->metaConfig,
                'twilio' => $this->twilioConfig,
            ],
        ]);

        session()->flash('whatsapp-settings-saved', __('whatsapp-bridge-settings::messages.notifications.saved'));
    }

    public function render()
    {
        return view('whatsapp-bridge-settings::livewire.whatsapp-connector');
    }
}

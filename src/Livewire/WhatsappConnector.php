<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Livewire;

use Livewire\Component;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;

class WhatsappConnector extends Component
{
    public string $status = 'disconnected';

    public ?string $qrCode = null;

    public ?string $connectedPhone = null;

    public string $testPhone = '';

    public string $testMessage = '';

    public ?string $sendResult = null;

    public bool $sendSuccess = false;

    public bool $isSending = false;

    public int $countdown = 0;

    public function mount(): void
    {
        $this->checkStatus();
    }

    public function checkStatus(): void
    {
        $whatsapp = app(WhatsappProviderInterface::class);

        $this->status = $whatsapp->getConnectionStatus();

        if ($this->status === 'connected') {
            $this->connectedPhone = $whatsapp->getConnectedPhone();
            $this->qrCode = null;
            $this->countdown = 0;
        } elseif ($this->status === 'waiting') {
            $this->connectedPhone = null;
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

        $success = $whatsapp->sendMessage(
            $this->testPhone,
            $this->testMessage
        );

        $this->sendSuccess = $success;
        $this->sendResult = $success
            ? __('whatsapp-bridge-settings::messages.notifications.test_sent')
            : __('whatsapp-bridge-settings::messages.notifications.test_failed');
        $this->isSending = false;
    }

    public function render()
    {
        return view('whatsapp-bridge-settings::livewire.whatsapp-connector');
    }
}

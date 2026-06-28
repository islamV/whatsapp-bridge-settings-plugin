<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Enums\WhatsappProvider;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

/**
 * @property-read Schema $form
 */
class WhatsappSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $slug = 'whatsapp-settings';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public string $status = 'disconnected';

    public ?string $qrCode = null;

    public ?string $connectedPhone = null;

    public bool $hasBridgeApiToken = false;

    public bool $hasMetaAccessToken = false;

    public bool $hasMetaAppSecret = false;

    public function mount(): void
    {
        $this->fillForm();
        $this->checkStatus();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    EmbeddedSchema::make('form'),
                ])
                    ->id('whatsapp-settings-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('whatsapp-bridge-settings::messages.general.save'))
                                ->icon('heroicon-o-check')
                                ->color('success')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make(__('whatsapp-bridge-settings::messages.page_heading'))
                    ->persistTabInQueryString('whatsapp-tab')
                    ->tabs([
                        Tab::make(__('whatsapp-bridge-settings::messages.tabs.general'))
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make(__('whatsapp-bridge-settings::messages.general.select_provider'))
                                    ->description(__('whatsapp-bridge-settings::messages.general.provider_hint'))
                                    ->schema([
                                        ToggleButtons::make('active_provider')
                                            ->label(__('whatsapp-bridge-settings::messages.general.select_provider'))
                                            ->inline()
                                            ->live()
                                            ->options($this->getProviderOptions())
                                            ->icons($this->getProviderIcons())
                                            ->colors($this->getProviderColors())
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('whatsapp-bridge-settings::messages.general.settings'))
                                    ->description(__('whatsapp-bridge-settings::messages.general.controls_description'))
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('otp_enabled')
                                            ->label(__('whatsapp-bridge-settings::messages.general.otp_enabled'))
                                            ->helperText(__('whatsapp-bridge-settings::messages.general.otp_enabled_help')),
                                        Toggle::make('messages_enabled')
                                            ->label(__('whatsapp-bridge-settings::messages.general.messages_enabled'))
                                            ->helperText(__('whatsapp-bridge-settings::messages.general.messages_enabled_help')),
                                        Textarea::make('otp_template')
                                            ->label(__('whatsapp-bridge-settings::messages.general.otp_template'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.general.otp_template_placeholder'))
                                            ->helperText(__('whatsapp-bridge-settings::messages.general.otp_template_helper'))
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make(__('whatsapp-bridge-settings::messages.tabs.bridge'))
                            ->icon('heroicon-o-link')
                            ->schema([
                                Section::make(__('whatsapp-bridge-settings::messages.bridge.card_title'))
                                    ->description(__('whatsapp-bridge-settings::messages.bridge.card_description'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('providers.bridge.api_base_url')
                                            ->label(__('whatsapp-bridge-settings::messages.bridge.api_base_url'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.fields.api_base_url_placeholder'))
                                            ->url(),
                                        TextInput::make('providers.bridge.api_token')
                                            ->label(__('whatsapp-bridge-settings::messages.bridge.api_token'))
                                            ->placeholder($this->hasBridgeApiToken
                                                ? __('whatsapp-bridge-settings::messages.fields.api_token_placeholder_keep')
                                                : __('whatsapp-bridge-settings::messages.bridge.api_token_placeholder'))
                                            ->helperText($this->hasBridgeApiToken
                                                ? __('whatsapp-bridge-settings::messages.fields.api_token_placeholder_keep')
                                                : null)
                                            ->password(),
                                        TextInput::make('providers.bridge.sender')
                                            ->label(__('whatsapp-bridge-settings::messages.bridge.sender'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.bridge.sender_placeholder')),
                                        TextInput::make('providers.bridge.timeout')
                                            ->label(__('whatsapp-bridge-settings::messages.bridge.timeout'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(300)
                                            ->default(30),
                                    ]),
                            ]),
                        Tab::make(__('whatsapp-bridge-settings::messages.tabs.meta'))
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Section::make(__('whatsapp-bridge-settings::messages.meta.card_title'))
                                    ->description(__('whatsapp-bridge-settings::messages.meta.card_description'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('providers.meta.phone_number_id')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.phone_number_id'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.meta.phone_number_id_placeholder')),
                                        TextInput::make('providers.meta.access_token')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.access_token'))
                                            ->placeholder($this->hasMetaAccessToken
                                                ? __('whatsapp-bridge-settings::messages.fields.api_token_placeholder_keep')
                                                : __('whatsapp-bridge-settings::messages.meta.access_token_placeholder'))
                                            ->helperText($this->hasMetaAccessToken
                                                ? __('whatsapp-bridge-settings::messages.fields.api_token_placeholder_keep')
                                                : null)
                                            ->password(),
                                        TextInput::make('providers.meta.business_account_id')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.business_account_id'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.meta.business_account_id_placeholder')),
                                        TextInput::make('providers.meta.verify_token')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.verify_token'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.meta.verify_token_placeholder')),
                                        TextInput::make('providers.meta.app_secret')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.app_secret'))
                                            ->placeholder($this->hasMetaAppSecret
                                                ? __('whatsapp-bridge-settings::messages.fields.api_token_placeholder_keep')
                                                : __('whatsapp-bridge-settings::messages.meta.app_secret_placeholder'))
                                            ->helperText($this->hasMetaAppSecret
                                                ? __('whatsapp-bridge-settings::messages.fields.api_token_placeholder_keep')
                                                : null)
                                            ->password(),
                                        TextInput::make('providers.meta.timeout')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.timeout'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(300)
                                            ->default(30),
                                    ]),
                            ]),
                        Tab::make(__('whatsapp-bridge-settings::messages.tabs.twilio'))
                            ->icon('heroicon-o-cloud')
                            ->schema([
                                Section::make(__('whatsapp-bridge-settings::messages.twilio.card_title'))
                                    ->description(__('whatsapp-bridge-settings::messages.twilio.card_description'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('providers.twilio.account_sid')
                                            ->label(__('whatsapp-bridge-settings::messages.twilio.account_sid'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.twilio.account_sid_placeholder')),
                                        TextInput::make('providers.twilio.auth_token')
                                            ->label(__('whatsapp-bridge-settings::messages.twilio.auth_token'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.twilio.auth_token_placeholder'))
                                            ->password(),
                                        TextInput::make('providers.twilio.from_number')
                                            ->label(__('whatsapp-bridge-settings::messages.twilio.from_number'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.twilio.from_number_placeholder')),
                                        TextInput::make('providers.twilio.timeout')
                                            ->label(__('whatsapp-bridge-settings::messages.twilio.timeout'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(300)
                                            ->default(30),
                                    ]),
                            ]),
                        Tab::make(__('whatsapp-bridge-settings::messages.tabs.status'))
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Section::make(__('whatsapp-bridge-settings::messages.tabs.status'))
                                    ->description(__('whatsapp-bridge-settings::messages.status.description'))
                                    ->schema([
                                        Placeholder::make('status_summary')
                                            ->hidden(fn (Get $get): bool => $get('active_provider') !== 'bridge')
                                            ->label(__('whatsapp-bridge-settings::messages.status.current'))
                                            ->content(fn (): HtmlString => $this->renderConnectionSummary()),
                                        Placeholder::make('status_provider_note')
                                            ->hidden(fn (Get $get): bool => $get('active_provider') === 'bridge')
                                            ->label(__('whatsapp-bridge-settings::messages.status.current'))
                                            ->content(__('whatsapp-bridge-settings::messages.status.bridge_only')),
                                        Actions::make([
                                            Action::make('refreshStatus')
                                                ->label(__('whatsapp-bridge-settings::messages.status.refresh'))
                                                ->icon('heroicon-o-arrow-path')
                                                ->color('gray')
                                                ->outlined()
                                                ->action('checkStatus')
                                                ->hidden(fn (Get $get): bool => $get('active_provider') !== 'bridge'),
                                            Action::make('generateQr')
                                                ->label(__('whatsapp-bridge-settings::messages.qr.connect_button'))
                                                ->icon('heroicon-o-qr-code')
                                                ->color('success')
                                                ->action('generateQr')
                                                ->hidden(fn (Get $get): bool => $get('active_provider') !== 'bridge' || $this->status === 'connected'),
                                            Action::make('disconnect')
                                                ->label(__('whatsapp-bridge-settings::messages.qr.disconnect_button'))
                                                ->icon('heroicon-o-link-slash')
                                                ->color('danger')
                                                ->requiresConfirmation()
                                                ->action('disconnect')
                                                ->hidden(fn (Get $get): bool => $get('active_provider') !== 'bridge' || $this->status === 'disconnected'),
                                        ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestMessage')
                ->label(__('whatsapp-bridge-settings::messages.actions.send_test'))
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->form([
                    TextInput::make('test_phone')
                        ->label(__('whatsapp-bridge-settings::messages.test_form.phone'))
                        ->placeholder(__('whatsapp-bridge-settings::messages.test_form.phone_placeholder'))
                        ->required()
                        ->maxLength(20),

                    Textarea::make('test_message')
                        ->label(__('whatsapp-bridge-settings::messages.test_form.message'))
                        ->placeholder(__('whatsapp-bridge-settings::messages.test_form.message_placeholder'))
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $whatsapp = app(WhatsappProviderInterface::class);

                    $success = $whatsapp->sendMessage(
                        $data['test_phone'],
                        $data['test_message']
                    );

                    if ($success) {
                        Notification::make()
                            ->title(__('whatsapp-bridge-settings::messages.notifications.test_sent'))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('whatsapp-bridge-settings::messages.notifications.test_failed'))
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        app(WhatsappSettingsRepository::class)->save($state);

        Notification::make()
            ->title(__('whatsapp-bridge-settings::messages.notifications.saved'))
            ->success()
            ->send();

        $this->fillForm();
        $this->checkStatus();
    }

    public function checkStatus(): void
    {
        $whatsapp = app(WhatsappProviderInterface::class);

        $this->status = $whatsapp->getConnectionStatus();
        $this->connectedPhone = $this->status === 'connected'
            ? $whatsapp->getConnectedPhone()
            : null;

        if ($this->status !== 'waiting') {
            $this->qrCode = null;
        }
    }

    public function generateQr(): void
    {
        $whatsapp = app(WhatsappProviderInterface::class);

        $this->qrCode = $whatsapp->generateQrCode();
        $this->status = $this->qrCode ? 'waiting' : 'disconnected';
        $this->connectedPhone = null;
    }

    public function disconnect(): void
    {
        app(WhatsappProviderInterface::class)->disconnect();

        $this->status = 'disconnected';
        $this->qrCode = null;
        $this->connectedPhone = null;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('whatsapp-bridge-settings::messages.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('whatsapp-bridge-settings::messages.navigation_label');
    }

    public function getTitle(): string
    {
        return __('whatsapp-bridge-settings::messages.page_title');
    }

    public function getHeading(): string
    {
        return __('whatsapp-bridge-settings::messages.page_heading');
    }

    protected function fillForm(): void
    {
        $settings = app(WhatsappSettingsRepository::class)->safeSettings();
        $providers = $settings['providers'] ?? [];

        $bridgeConfig = array_replace([
            'api_base_url' => '',
            'api_token' => '',
            'sender' => '',
            'timeout' => 30,
        ], $providers['bridge'] ?? []);

        $metaConfig = array_replace([
            'phone_number_id' => '',
            'access_token' => '',
            'business_account_id' => '',
            'verify_token' => '',
            'app_secret' => '',
            'timeout' => 30,
        ], $providers['meta'] ?? []);

        $twilioConfig = array_replace([
            'account_sid' => '',
            'auth_token' => '',
            'from_number' => '',
            'timeout' => 30,
        ], $providers['twilio'] ?? []);

        $this->hasBridgeApiToken = (bool) ($bridgeConfig['has_api_token'] ?? false);
        $this->hasMetaAccessToken = (bool) ($metaConfig['has_access_token'] ?? false);
        $this->hasMetaAppSecret = (bool) ($metaConfig['has_app_secret'] ?? false);

        if ($this->hasBridgeApiToken) {
            $bridgeConfig['api_token'] = '';
        }

        if ($this->hasMetaAccessToken) {
            $metaConfig['access_token'] = '';
        }

        if ($this->hasMetaAppSecret) {
            $metaConfig['app_secret'] = '';
        }

        unset(
            $bridgeConfig['has_api_token'],
            $metaConfig['has_access_token'],
            $metaConfig['has_app_secret']
        );

        $this->form->fill([
            'active_provider' => $settings['active_provider'] ?? 'bridge',
            'otp_enabled' => $settings['otp_enabled'] ?? true,
            'messages_enabled' => $settings['messages_enabled'] ?? true,
            'otp_template' => $settings['otp_template'] ?? 'Your verification code is: {otp}',
            'providers' => [
                'bridge' => $bridgeConfig,
                'meta' => $metaConfig,
                'twilio' => $twilioConfig,
            ],
        ]);
    }

    protected function getProviderOptions(): array
    {
        return collect(WhatsappProvider::cases())
            ->mapWithKeys(fn (WhatsappProvider $provider) => [$provider->value => $provider->getLabel()])
            ->all();
    }

    protected function getProviderIcons(): array
    {
        return [
            'bridge' => 'heroicon-o-link',
            'meta' => 'heroicon-o-globe-alt',
            'twilio' => 'heroicon-o-cloud',
        ];
    }

    protected function getProviderColors(): array
    {
        return [
            'bridge' => 'success',
            'meta' => 'info',
            'twilio' => 'danger',
        ];
    }

    protected function renderConnectionSummary(): HtmlString
    {
        $label = match ($this->status) {
            'connected' => __('whatsapp-bridge-settings::messages.status.connected'),
            'waiting' => __('whatsapp-bridge-settings::messages.status.waiting'),
            default => __('whatsapp-bridge-settings::messages.status.disconnected'),
        };

        $color = match ($this->status) {
            'connected' => 'success',
            'waiting' => 'warning',
            default => 'danger',
        };

        $phone = $this->connectedPhone
            ? '<p class="text-sm text-gray-600 dark:text-gray-300">' . e(__('whatsapp-bridge-settings::messages.qr.connected_phone', ['phone' => $this->connectedPhone])) . '</p>'
            : '';

        $qr = '';

        if ($this->status === 'waiting') {
            if ($this->qrCode && (str_starts_with($this->qrCode, 'data:image') || str_contains($this->qrCode, 'base64'))) {
                $qr = '<div class="mt-4"><img src="' . e($this->qrCode) . '" alt="' . e(__('whatsapp-bridge-settings::messages.qr.qr_image_alt')) . '" class="max-w-xs rounded-xl border border-gray-200 bg-white p-3" /></div>';
            } elseif ($this->qrCode) {
                $qr = '<div class="mt-4 rounded-xl border border-gray-200 bg-white p-3">' . $this->qrCode . '</div>';
            } else {
                $qr = '<p class="mt-4 text-sm text-gray-600 dark:text-gray-300">' . e(__('whatsapp-bridge-settings::messages.qr.qr_generating')) . '</p>';
            }
        }

        return new HtmlString(
            '<div class="space-y-3">' .
                '<span class="fi-badge fi-color-' . $color . '">' . e($label) . '</span>' .
                $phone .
                $qr .
            '</div>'
        );
    }
}

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

use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Enums\WhatsappProvider;
use Islamv\WhatsappBridgeSettingsPlugin\Services\WhatsappBridge;
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

    /**
     * Result of the last GET /health call to the bridge service.
     * Shape: ['reachable' => bool, 'status' => string|null, 'latency_ms' => int|null, 'url' => string|null]
     */
    public array $bridgeHealth = ['reachable' => false, 'status' => null, 'latency_ms' => null, 'url' => null];

    public function mount(): void
    {
        $this->fillForm();
        $this->checkBridgeHealth();
        $this->checkStatus();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    EmbeddedSchema::make('form'),
                ])
                    ->id('whatsapp-settings-form'),
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
                                Actions::make([
                                    Action::make('saveGeneral')
                                        ->label(__('whatsapp-bridge-settings::messages.general.save'))
                                        ->icon('heroicon-o-check')
                                        ->color('success')
                                        ->action('saveGeneral')
                                        ->keyBindings(['mod+s']),
                                ]),
                            ]),
                        Tab::make(__('whatsapp-bridge-settings::messages.tabs.bridge'))
                            ->icon('heroicon-o-link')
                            ->schema([

                                // ── Connection Overview ──────────────────────────────────────────
                                Section::make(__('whatsapp-bridge-settings::messages.bridge.overview_title'))
                                    ->description(__('whatsapp-bridge-settings::messages.bridge.overview_description'))
                                    ->schema([
                                        Placeholder::make('connection_overview')
                                            ->label('')
                                            ->content(fn (): HtmlString => $this->renderConnectionOverview()),
                                        Actions::make([
                                            Action::make('checkBridgeHealth')
                                                ->label(__('whatsapp-bridge-settings::messages.bridge.health_check_button'))
                                                ->icon('heroicon-o-signal')
                                                ->color('gray')
                                                ->outlined()
                                                ->action('checkBridgeHealth'),
                                            Action::make('refreshStatus')
                                                ->label(__('whatsapp-bridge-settings::messages.status.refresh'))
                                                ->icon('heroicon-o-arrow-path')
                                                ->color('gray')
                                                ->outlined()
                                                ->action('checkStatus'),
                                            Action::make('generateQr')
                                                ->label(__('whatsapp-bridge-settings::messages.qr.connect_button'))
                                                ->icon('heroicon-o-qr-code')
                                                ->color('success')
                                                ->action('generateQr')
                                                ->hidden(fn (): bool => $this->status === 'connected'),
                                            Action::make('disconnect')
                                                ->label(__('whatsapp-bridge-settings::messages.qr.disconnect_button'))
                                                ->icon('heroicon-o-link-slash')
                                                ->color('danger')
                                                ->requiresConfirmation()
                                                ->action('disconnect')
                                                ->hidden(fn (): bool => $this->status === 'disconnected'),
                                        ]),
                                    ]),

                                // ── Connection Settings ──────────────────────────────────────────
                                Section::make(__('whatsapp-bridge-settings::messages.bridge.card_title'))
                                    ->description(__('whatsapp-bridge-settings::messages.bridge.card_description'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('providers.bridge.api_base_url')
                                            ->label(__('whatsapp-bridge-settings::messages.bridge.api_base_url'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.fields.api_base_url_placeholder'))
                                            ->url()
                                            ->columnSpan(1),
                                        TextInput::make('providers.bridge.api_token')
                                            ->label(__('whatsapp-bridge-settings::messages.bridge.api_token'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.bridge.api_token_placeholder'))
                                            ->password()
                                            ->revealable()
                                            ->columnSpan(1),
                                        TextInput::make('providers.bridge.sender')
                                            ->label(__('whatsapp-bridge-settings::messages.bridge.sender'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.bridge.sender_placeholder'))
                                            ->columnSpan(1),
                                        TextInput::make('providers.bridge.timeout')
                                            ->label(__('whatsapp-bridge-settings::messages.bridge.request_timeout'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(300)
                                            ->default(30)
                                            ->suffix(__('whatsapp-bridge-settings::messages.bridge.timeout_suffix'))
                                            ->columnSpan(1),
                                        Actions::make([
                                            Action::make('saveBridge')
                                                ->label(__('whatsapp-bridge-settings::messages.bridge.save_changes'))
                                                ->icon('heroicon-o-check')
                                                ->color('success')
                                                ->action('saveBridge'),
                                        ])->columnSpanFull(),
                                    ]),

                            ]),
                        Tab::make(__('whatsapp-bridge-settings::messages.tabs.meta'))
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
                                            ->placeholder(__('whatsapp-bridge-settings::messages.meta.access_token_placeholder'))
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('providers.meta.business_account_id')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.business_account_id'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.meta.business_account_id_placeholder')),
                                        TextInput::make('providers.meta.verify_token')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.verify_token'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.meta.verify_token_placeholder')),
                                        TextInput::make('providers.meta.app_secret')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.app_secret'))
                                            ->placeholder(__('whatsapp-bridge-settings::messages.meta.app_secret_placeholder'))
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('providers.meta.timeout')
                                            ->label(__('whatsapp-bridge-settings::messages.meta.timeout'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(300)
                                            ->default(30),
                                    ]),
                                Actions::make([
                                    Action::make('saveMeta')
                                        ->label(__('whatsapp-bridge-settings::messages.general.save'))
                                        ->icon('heroicon-o-check')
                                        ->color('success')
                                        ->action('saveMeta'),
                                ]),
                            ]),
                        Tab::make(__('whatsapp-bridge-settings::messages.tabs.twilio'))
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
                                            ->password()
                                            ->revealable(),
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
                                Actions::make([
                                    Action::make('saveTwilio')
                                        ->label(__('whatsapp-bridge-settings::messages.general.save'))
                                        ->icon('heroicon-o-check')
                                        ->color('success')
                                        ->action('saveTwilio'),
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

                    Notification::make()
                        ->title($success
                            ? __('whatsapp-bridge-settings::messages.notifications.test_sent')
                            : __('whatsapp-bridge-settings::messages.notifications.test_failed'))
                        ->{$success ? 'success' : 'danger'}()
                        ->send();
                }),
        ];
    }

    public function saveGeneral(): void
    {
        $state = $this->form->getState();
        
        app(WhatsappSettingsRepository::class)->saveGeneral($state);

        Notification::make()
            ->title(__('whatsapp-bridge-settings::messages.notifications.saved'))
            ->success()
            ->send();

        $this->fillForm();
        $this->checkStatus();
    }

    public function saveBridge(): void
    {
        $state = $this->form->getState();
        $config = $state['providers']['bridge'] ?? [];

        app(WhatsappSettingsRepository::class)->saveProvider('bridge', $config);

        Notification::make()
            ->title(__('whatsapp-bridge-settings::messages.notifications.saved'))
            ->success()
            ->send();

        $this->fillForm();
        $this->checkBridgeHealth();
        $this->checkStatus();
    }

    public function saveMeta(): void
    {
        $state = $this->form->getState();
        $config = $state['providers']['meta'] ?? [];

        app(WhatsappSettingsRepository::class)->saveProvider('meta', $config);

        Notification::make()
            ->title(__('whatsapp-bridge-settings::messages.notifications.saved'))
            ->success()
            ->send();

        $this->fillForm();
        $this->checkStatus();
    }

    public function saveTwilio(): void
    {
        $state = $this->form->getState();
        $config = $state['providers']['twilio'] ?? [];

        app(WhatsappSettingsRepository::class)->saveProvider('twilio', $config);

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

    public function checkBridgeHealth(): void
    {
        // Prefer the live form value (unsaved edits), but fall back to the
        // persisted setting so the initial page-load check always works even
        // before Livewire syncs $this->data.
        $formUrl = data_get($this->data, 'providers.bridge.api_base_url');

        if (empty($formUrl)) {
            $settings = app(WhatsappSettingsRepository::class)->all();
            $formUrl  = $settings['providers']['bridge']['api_base_url'] ?? null;
        }

        $bridge = app(WhatsappBridge::class);
        $this->bridgeHealth = $bridge->checkBridgeHealth($formUrl ?: null);
    }

    public function generateQr(): void
    {
        $state = $this->form->getState();
        $config = $state['providers']['bridge'] ?? [];

        // Persist the current form values so the QR request uses them.
        app(WhatsappSettingsRepository::class)->saveProvider('bridge', $config);
        $this->fillForm();

        // Resolve a fresh WhatsappBridge instance (bypassing the singleton cache)
        // so it picks up the settings we just saved.
        /** @var WhatsappBridge $bridge */
        $bridge = app()->make(WhatsappBridge::class);

        $this->qrCode = $bridge->generateQrCode();
        $this->status = $this->qrCode ? 'waiting' : $bridge->getConnectionStatus();
        $this->connectedPhone = $this->status === 'connected'
            ? $bridge->getConnectedPhone()
            : null;

        if (! $this->qrCode && $this->status !== 'connected') {
            Notification::make()
                ->title(__('whatsapp-bridge-settings::messages.notifications.qr_failed'))
                ->danger()
                ->send();
        }
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
        $settings = app(WhatsappSettingsRepository::class)->all();
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

        $this->hasBridgeApiToken = filled($bridgeConfig['api_token'] ?? null);
        $this->hasMetaAccessToken = filled($metaConfig['access_token'] ?? null);
        $this->hasMetaAppSecret = filled($metaConfig['app_secret'] ?? null);

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

    protected function getProviderColors(): array
    {
        return collect(WhatsappProvider::cases())
            ->mapWithKeys(fn (WhatsappProvider $provider) => [$provider->value => $provider->getColor()])
            ->all();
    }

    protected function renderConnectionOverview(): HtmlString
    {
        // ── Bridge API status ────────────────────────────────────────────────
        $reachable     = $this->bridgeHealth['reachable'] ?? false;
        $latency       = $this->bridgeHealth['latency_ms'] ?? null;
        $serviceStatus = $this->bridgeHealth['status'] ?? null;
        $checkedUrl    = $this->bridgeHealth['url'] ?? null;
        $formUrl       = data_get($this->data, 'providers.bridge.api_base_url');
        $hasUrl        = ! empty($formUrl) || ! empty($checkedUrl);
        $displayUrl    = $formUrl ?: ($checkedUrl ? rtrim(str_replace('/health', '', $checkedUrl), '/') : null);

        if ($reachable) {
            $apiDot    = '<span class="relative inline-flex h-2 w-2">' .
                             '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>' .
                             '<span class="relative inline-flex h-2 w-2 rounded-full bg-green-500 dark:bg-green-400"></span>' .
                         '</span>';
            $apiBadge  = 'inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20';
            $apiLabel  = e(__('whatsapp-bridge-settings::messages.bridge.health_reachable'));
            $apiDetail = $latency !== null ? '<span class="text-xs text-gray-400 dark:text-gray-500">(' . e($latency) . ' ms)</span>' : '';
        } elseif (! $hasUrl) {
            $apiDot    = '<span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>';
            $apiBadge  = 'inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20';
            $apiLabel  = e(__('whatsapp-bridge-settings::messages.bridge.health_not_configured'));
            $apiDetail = '';
        } else {
            $apiDot    = '<span class="relative inline-flex h-2 w-2">' .
                             '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>' .
                             '<span class="relative inline-flex h-2 w-2 rounded-full bg-red-500 dark:bg-red-400"></span>' .
                         '</span>';
            $apiBadge  = 'inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20';
            $apiLabel  = e(__('whatsapp-bridge-settings::messages.bridge.health_unreachable'));
            $apiDetail = '';
        }

        $urlRow = $displayUrl
            ? '<p class="mt-1 font-mono text-xs text-gray-400 dark:text-gray-500 truncate max-w-45">' . e(preg_replace('#^https?://#', '', $displayUrl)) . '</p>'
            : '';

        // ── WhatsApp connection status ───────────────────────────────────────
        $waLabel = match ($this->status) {
            'connected'    => __('whatsapp-bridge-settings::messages.status.connected'),
            'waiting'      => __('whatsapp-bridge-settings::messages.status.waiting'),
            default        => __('whatsapp-bridge-settings::messages.status.disconnected'),
        };

        [$waDot, $waBadge] = match ($this->status) {
            'connected' => [
                '<span class="relative inline-flex h-2 w-2">' .
                    '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>' .
                    '<span class="relative inline-flex h-2 w-2 rounded-full bg-green-500 dark:bg-green-400"></span>' .
                '</span>',
                'inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20',
            ],
            'waiting' => [
                '<span class="relative inline-flex h-2 w-2">' .
                    '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>' .
                    '<span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500 dark:bg-amber-400"></span>' .
                '</span>',
                'inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/20',
            ],
            default => [
                '<span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>',
                'inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20',
            ],
        };

        $phoneRow = $this->connectedPhone
            ? '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400">' . e($this->connectedPhone) . '</p>'
            : '';

        // ── Instance / Sender ────────────────────────────────────────────────
        $sender       = data_get($this->data, 'providers.bridge.sender') ?: __('whatsapp-bridge-settings::messages.bridge.no_instance');
        $instanceHtml = '<p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">' . e($sender) . '</p>';

        // ── QR code (waiting state) ──────────────────────────────────────────
        $qrHtml = '';
        if ($this->status === 'waiting') {
            if ($this->qrCode && (str_starts_with($this->qrCode, 'data:image') || str_contains($this->qrCode, 'base64'))) {
                $qrHtml = '<div class="mt-5 border-t border-gray-100 dark:border-white/10 pt-4">' .
                              '<p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">' . e(__('whatsapp-bridge-settings::messages.qr.qr_scan_title')) . '</p>' .
                              '<img src="' . e($this->qrCode) . '" alt="' . e(__('whatsapp-bridge-settings::messages.qr.qr_image_alt')) . '" class="max-w-45 rounded-xl border border-gray-200 dark:border-white/10 bg-white p-2" />' .
                          '</div>';
            } elseif ($this->qrCode) {
                $qrHtml = '<div class="mt-5 border-t border-gray-100 dark:border-white/10 pt-4">' .
                              '<div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white p-3 max-w-55">' . $this->qrCode . '</div>' .
                          '</div>';
            } else {
                $qrHtml = '<p class="mt-4 text-sm text-gray-500 dark:text-gray-400">' . e(__('whatsapp-bridge-settings::messages.qr.qr_generating')) . '</p>';
            }
        }

        // ── Bridge unavailable warning (when URL set but unreachable) ────────
        $warningHtml = '';
        if (! $reachable && $hasUrl) {
            $warningHtml =
                '<div class="mt-4 rounded-lg border border-red-200 bg-red-50 dark:border-red-500/20 dark:bg-red-400/10 px-4 py-3 flex items-start gap-3">' .
                '<svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">' .
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />' .
                    '</svg>' .
                    '<div>' .
                        '<p class="text-sm font-medium text-red-700 dark:text-red-400">' . e(__('whatsapp-bridge-settings::messages.bridge.bridge_unavailable_title')) . '</p>' .
                        '<p class="mt-0.5 text-xs text-red-600 dark:text-red-300">' . e(__('whatsapp-bridge-settings::messages.bridge.bridge_unavailable_body')) . '</p>' .
                    '</div>' .
                '</div>';
        }

        // ── Assemble 3-column grid ───────────────────────────────────────────
        $grid =
            '<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">' .

                // Bridge API column
                '<div>' .
                    '<p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">' .
                        e(__('whatsapp-bridge-settings::messages.bridge.bridge_api_label')) .
                    '</p>' .
                    '<div class="flex items-center gap-2">' .
                        '<span class="' . $apiBadge . '">' . $apiDot . $apiLabel . '</span>' .
                        $apiDetail .
                    '</div>' .
                    $urlRow .
                '</div>' .

                // WhatsApp column
                '<div>' .
                    '<p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">' .
                        e(__('whatsapp-bridge-settings::messages.bridge.whatsapp_label')) .
                    '</p>' .
                    '<span class="' . $waBadge . '">' . $waDot . e($waLabel) . '</span>' .
                    $phoneRow .
                '</div>' .

                // Instance column
                '<div>' .
                    '<p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">' .
                        e(__('whatsapp-bridge-settings::messages.bridge.instance_label')) .
                    '</p>' .
                    $instanceHtml .
                '</div>' .

            '</div>';

        return new HtmlString($grid . $warningHtml . $qrHtml);
    }
}


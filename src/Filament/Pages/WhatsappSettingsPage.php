<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\ViewField;
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
                                        ViewField::make('otp_template')
                                            ->view('whatsapp-bridge-settings::otp-template-editor')
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
                                // ── Connection Overview Dashboard ──────────────────────────────────
                                Section::make(__('whatsapp-bridge-settings::messages.bridge.overview_title'))
                                    ->description(__('whatsapp-bridge-settings::messages.bridge.overview_description'))
                                    ->schema([
                                        Placeholder::make('connection_overview_dashboard')
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

                                // ── Bridge Connection Settings ───────────────────────────────────
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

    public function getSubheading(): ?string
    {
        return __('whatsapp-bridge-settings::messages.page_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestMessage')
                ->label(__('whatsapp-bridge-settings::messages.actions.send_test'))
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->disabled(fn (): bool => $this->status !== 'connected')
                ->tooltip(fn (): ?string => $this->status !== 'connected' ? __('whatsapp-bridge-settings::messages.bridge.send_test_disabled_tooltip') : null)
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
        // ── 1. Bridge API state ─────────────────────────────────────────────────
        $reachable       = $this->bridgeHealth['reachable'] ?? false;
        $latency         = $this->bridgeHealth['latency_ms'] ?? null;
        $checkedUrl      = $this->bridgeHealth['url'] ?? null;
        $formUrl         = data_get($this->data, 'providers.bridge.api_base_url');
        $hasUrl          = ! empty($formUrl) || ! empty($checkedUrl);
        $displayUrl      = $formUrl ?: ($checkedUrl ? rtrim(str_replace('/health', '', $checkedUrl), '/') : '—');
        $cleanDisplayUrl = $displayUrl !== '—' ? preg_replace('#^https?://#', '', $displayUrl) : '—';

        if ($reachable) {
            $apiDot = '<span class="relative inline-flex h-2 w-2">' .
                          '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>' .
                          '<span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>' .
                      '</span>';
            $apiBadge = 'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20';
            $apiStatusText = __('whatsapp-bridge-settings::messages.bridge.health_reachable');

            if ($latency !== null) {
                $latencyColor = match (true) {
                    $latency < 300 => 'text-emerald-600 dark:text-emerald-400 font-medium',
                    $latency <= 800 => 'text-amber-600 dark:text-amber-400 font-medium',
                    default => 'text-rose-600 dark:text-rose-400 font-medium',
                };
                $latencyHtml = '<span class="' . $latencyColor . '">⚡ ' . e($latency) . ' ms</span>';
            } else {
                $latencyHtml = '<span class="text-gray-400 dark:text-gray-500">—</span>';
            }
        } elseif (! $hasUrl) {
            $apiDot = '<span class="inline-block h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>';
            $apiBadge = 'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400';
            $apiStatusText = __('whatsapp-bridge-settings::messages.bridge.health_not_configured');
            $latencyHtml = '<span class="text-gray-400 dark:text-gray-500">—</span>';
        } else {
            $apiDot = '<span class="relative inline-flex h-2 w-2">' .
                          '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>' .
                          '<span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500 dark:bg-rose-400"></span>' .
                      '</span>';
            $apiBadge = 'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20';
            $apiStatusText = __('whatsapp-bridge-settings::messages.bridge.health_unreachable');
            $latencyHtml = '<span class="text-rose-500 dark:text-rose-400 font-medium">Offline</span>';
        }

        // ── 2. WhatsApp Session state ───────────────────────────────────────────
        $waStatusLabel = match ($this->status) {
            'connected' => __('whatsapp-bridge-settings::messages.status.connected'),
            'waiting'   => __('whatsapp-bridge-settings::messages.status.waiting'),
            default     => __('whatsapp-bridge-settings::messages.status.disconnected'),
        };

        [$waDot, $waBadgeClass] = match ($this->status) {
            'connected' => [
                '<span class="relative inline-flex h-2 w-2">' .
                    '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>' .
                    '<span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>' .
                '</span>',
                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20',
            ],
            'waiting' => [
                '<span class="relative inline-flex h-2 w-2">' .
                    '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>' .
                    '<span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500 dark:bg-amber-400"></span>' .
                '</span>',
                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20',
            ],
            default => [
                '<span class="inline-block h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>',
                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400',
            ],
        };

        $rawPhone = $this->connectedPhone;
        $cleanPhone = $rawPhone ? str_replace('@c.us', '', $rawPhone) : '—';

        $sessionStatusText = match ($this->status) {
            'connected' => __('whatsapp-bridge-settings::messages.bridge.active_session'),
            'waiting'   => __('whatsapp-bridge-settings::messages.bridge.scan_qr_hint'),
            default     => __('whatsapp-bridge-settings::messages.bridge.no_session'),
        };

        // ── 3. Instance state ───────────────────────────────────────────────────
        $senderId = data_get($this->data, 'providers.bridge.sender') ?: __('whatsapp-bridge-settings::messages.bridge.no_instance');

        // ── Unreachable warning alert ───────────────────────────────────────────
        $warningAlert = '';
        if (! $reachable && $hasUrl) {
            $warningAlert =
                '<div class="rounded-xl border border-rose-200 bg-rose-50/80 dark:border-rose-500/30 dark:bg-rose-500/10 p-4 flex items-start gap-3">' .
                    '<div class="p-1.5 rounded-lg bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5">' .
                        '<svg width="16" height="16" style="width:1rem;height:1rem;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' .
                    '</div>' .
                    '<div>' .
                        '<h4 class="text-xs font-semibold text-rose-800 dark:text-rose-300">' . e(__('whatsapp-bridge-settings::messages.bridge.bridge_unreachable_title')) . '</h4>' .
                        '<p class="text-xs text-rose-600 dark:text-rose-400 mt-0.5">' . e(__('whatsapp-bridge-settings::messages.bridge.bridge_unreachable_body')) . '</p>' .
                    '</div>' .
                '</div>';
        }

        // ── QR Code Section ─────────────────────────────────────────────────────
        $qrSection = '';
        if ($this->status === 'waiting') {
            $qrContent = '';
            if ($this->qrCode && (str_starts_with($this->qrCode, 'data:image') || str_contains($this->qrCode, 'base64'))) {
                $qrContent = '<img src="' . e($this->qrCode) . '" alt="' . e(__('whatsapp-bridge-settings::messages.qr.qr_image_alt')) . '" class="max-w-45 rounded-xl border border-gray-200 dark:border-gray-700 p-2 bg-white shadow-xs" />';
            } elseif ($this->qrCode) {
                $qrContent = '<div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 bg-white max-w-55 shadow-xs">' . $this->qrCode . '</div>';
            } else {
                $qrContent = '<div class="flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400"><svg width="16" height="16" style="width:1rem;height:1rem;" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>' . e(__('whatsapp-bridge-settings::messages.qr.qr_generating')) . '</div>';
            }

            $qrSection =
                '<div class="mt-4 p-5 bg-white dark:bg-gray-800 rounded-xl border border-amber-200 dark:border-amber-500/30 shadow-xs text-center flex flex-col items-center justify-center">' .
                    '<h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">' . e(__('whatsapp-bridge-settings::messages.qr.qr_scan_title')) . '</h4>' .
                    '<p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4">Open WhatsApp > Linked Devices > Link a Device</p>' .
                    $qrContent .
                '</div>';
        }

        // ── Assemble Dashboard Grid ─────────────────────────────────────────────
        $dashboardHtml =
            '<div class="bg-slate-50/70 dark:bg-gray-900/40 p-4 sm:p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800 space-y-4">' .

                // Header / Live Indicator Row
                '<div class="flex items-center justify-between pb-1 border-b border-gray-200/60 dark:border-gray-800">' .
                    '<div class="flex items-center gap-2">' .
                        '<span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">' . e(__('whatsapp-bridge-settings::messages.bridge.overview_title')) . '</span>' .
                    '</div>' .
                    '<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20">' .
                        '<span class="relative inline-flex h-2 w-2">' .
                            '<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>' .
                            '<span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>' .
                        '</span>' .
                        e(__('whatsapp-bridge-settings::messages.bridge.live_badge')) .
                    '</span>' .
                '</div>' .

                // 3 Mini Cards Grid
                '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">' .

                    // 1. Bridge API Card
                    '<div class="bg-white dark:bg-gray-800/90 rounded-xl p-4 border border-gray-200/80 dark:border-gray-700/70 shadow-xs hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 flex flex-col justify-between space-y-3">' .
                        '<div>' .
                            '<div class="flex items-center justify-between mb-3">' .
                                '<div class="flex items-center gap-2.5">' .
                                    '<div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">' .
                                        '<svg style="width:16px!important;height:16px!important;max-width:16px!important;max-height:16px!important;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>' .
                                    '</div>' .
                                    '<span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">' . e(__('whatsapp-bridge-settings::messages.bridge.bridge_api_title')) . '</span>' .
                                '</div>' .
                            '</div>' .
                            '<div>' .
                                '<span class="' . $apiBadge . '">' . $apiDot . e($apiStatusText) . '</span>' .
                            '</div>' .
                        '</div>' .
                        '<div class="pt-2 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">' .
                            '<span class="font-mono text-gray-400 dark:text-gray-500 truncate max-w-40" title="' . e($cleanDisplayUrl) . '">' . e($cleanDisplayUrl) . '</span>' .
                            $latencyHtml .
                        '</div>' .
                    '</div>' .

                    // 2. WhatsApp Session Card
                    '<div class="bg-white dark:bg-gray-800/90 rounded-xl p-4 border border-gray-200/80 dark:border-gray-700/70 shadow-xs hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 flex flex-col justify-between space-y-3">' .
                        '<div>' .
                            '<div class="flex items-center justify-between mb-3">' .
                                '<div class="flex items-center gap-2.5">' .
                                    '<div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">' .
                                        '<svg style="width:16px!important;height:16px!important;max-width:16px!important;max-height:16px!important;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>' .
                                    '</div>' .
                                    '<span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">' . e(__('whatsapp-bridge-settings::messages.bridge.whatsapp_title')) . '</span>' .
                                '</div>' .
                            '</div>' .
                            '<div>' .
                                '<span class="' . $waBadgeClass . '">' . $waDot . e($waStatusLabel) . '</span>' .
                            '</div>' .
                        '</div>' .
                        '<div class="pt-2 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">' .
                            '<span class="font-medium text-gray-700 dark:text-gray-300 font-mono">' . e($cleanPhone) . '</span>' .
                            '<span class="text-gray-400 dark:text-gray-500">' . e($sessionStatusText) . '</span>' .
                        '</div>' .
                    '</div>' .

                    // 3. Instance Card
                    '<div class="bg-white dark:bg-gray-800/90 rounded-xl p-4 border border-gray-200/80 dark:border-gray-700/70 shadow-xs hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-200 flex flex-col justify-between space-y-3">' .
                        '<div>' .
                            '<div class="flex items-center justify-between mb-3">' .
                                '<div class="flex items-center gap-2.5">' .
                                    '<div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">' .
                                        '<svg style="width:16px!important;height:16px!important;max-width:16px!important;max-height:16px!important;" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>' .
                                    '</div>' .
                                    '<span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">' . e(__('whatsapp-bridge-settings::messages.bridge.instance_title')) . '</span>' .
                                '</div>' .
                            '</div>' .
                            '<div>' .
                                '<span class="font-mono text-sm font-semibold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700/60 px-2 py-0.5 rounded">' . e($senderId) . '</span>' .
                            '</div>' .
                        '</div>' .
                        '<div class="pt-2 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">' .
                            '<span class="text-gray-400 dark:text-gray-500">Instance ID</span>' .
                            '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400">' . e(__('whatsapp-bridge-settings::messages.bridge.active_instance')) . '</span>' .
                        '</div>' .
                    '</div>' .

                '</div>' .

                $warningAlert .

                $qrSection .

            '</div>';

        return new HtmlString($dashboardHtml);
    }
}

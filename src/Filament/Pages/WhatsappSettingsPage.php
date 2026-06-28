<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;
use Islamv\WhatsappBridgeSettingsPlugin\Settings\WhatsappSettingsRepository;

class WhatsappSettingsPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $slug = 'whatsapp-settings';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $repository = app(WhatsappSettingsRepository::class);
        $this->form->fill($repository->safeSettings());
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make(__('whatsapp-bridge-settings::messages.sections.api_configuration'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('provider_name')
                            ->label(__('whatsapp-bridge-settings::messages.fields.provider_name'))
                            ->placeholder(__('whatsapp-bridge-settings::messages.fields.provider_name_placeholder'))
                            ->maxLength(255),

                        TextInput::make('api_base_url')
                            ->label(__('whatsapp-bridge-settings::messages.fields.api_base_url'))
                            ->placeholder(__('whatsapp-bridge-settings::messages.fields.api_base_url_placeholder'))
                            ->maxLength(255)
                            ->url(),

                        TextInput::make('api_token')
                            ->label(__('whatsapp-bridge-settings::messages.fields.api_token'))
                            ->placeholder(
                                fn (Get $get): string => $get('has_token')
                                    ? __('whatsapp-bridge-settings::messages.fields.api_token_placeholder_keep')
                                    : __('whatsapp-bridge-settings::messages.fields.api_token_placeholder_enter')
                            )
                            ->password()
                            ->revealable()
                            ->maxLength(4096),

                        TextInput::make('sender')
                            ->label(__('whatsapp-bridge-settings::messages.fields.sender'))
                            ->placeholder(__('whatsapp-bridge-settings::messages.fields.sender_placeholder'))
                            ->maxLength(255),

                        TextInput::make('default_country_code')
                            ->label(__('whatsapp-bridge-settings::messages.fields.default_country_code'))
                            ->placeholder(__('whatsapp-bridge-settings::messages.fields.default_country_code_placeholder'))
                            ->maxLength(10)
                            ->default('20'),

                        TextInput::make('timeout')
                            ->label(__('whatsapp-bridge-settings::messages.fields.timeout'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300)
                            ->default(30),
                    ]),

                Section::make(__('whatsapp-bridge-settings::messages.sections.message_settings'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('otp_enabled')
                            ->label(__('whatsapp-bridge-settings::messages.fields.otp_enabled'))
                            ->default(true),

                        Toggle::make('messages_enabled')
                            ->label(__('whatsapp-bridge-settings::messages.fields.messages_enabled'))
                            ->default(true),
                    ]),

                Section::make(__('whatsapp-bridge-settings::messages.sections.message_templates'))
                    ->schema([
                        Textarea::make('otp_template')
                            ->label(__('whatsapp-bridge-settings::messages.fields.otp_template'))
                            ->placeholder(__('whatsapp-bridge-settings::messages.fields.otp_template_placeholder'))
                            ->rows(3)
                            ->helperText(__('whatsapp-bridge-settings::messages.fields.otp_template_helper')),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('whatsapp-bridge-settings::messages.actions.save'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        $repository = app(WhatsappSettingsRepository::class);

        $data = $this->form->getState();

        $repository->save($data);

        Notification::make()
            ->title(__('whatsapp-bridge-settings::messages.notifications.saved'))
            ->success()
            ->send();
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
}

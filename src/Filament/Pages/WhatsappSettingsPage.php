<?php

namespace Islamv\WhatsappBridgeSettingsPlugin\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Islamv\WhatsappBridgeSettingsPlugin\Contracts\WhatsappProviderInterface;

class WhatsappSettingsPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $slug = 'whatsapp-settings';

    protected static ?int $navigationSort = 99;

    protected string $view = 'whatsapp-bridge-settings::filament.pages.whatsapp-settings';

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

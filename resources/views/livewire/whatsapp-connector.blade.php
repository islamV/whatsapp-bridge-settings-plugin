<div class="mt-4" dir="rtl">
    @if ($status === 'waiting')
        <div wire:poll.3000ms="checkStatus" class="flex flex-col items-center justify-center p-6 border border-emerald-500 bg-white dark:bg-gray-800 rounded-2xl shadow-md max-w-sm mx-auto">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('whatsapp-bridge-settings::messages.qr.qr_scan_title') }}</h3>

            @if ($qrCode)
                <div class="w-64 h-64 bg-white p-3 border-2 border-emerald-500 rounded-xl shadow-inner flex items-center justify-center">
                    @if (str_starts_with($qrCode, 'data:image') || str_contains($qrCode, 'base64'))
                        <img src="{{ $qrCode }}" alt="WhatsApp QR Code" class="w-full h-full object-contain" />
                    @else
                        {!! $qrCode !!}
                    @endif
                </div>
                <div class="mt-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ __('whatsapp-bridge-settings::messages.qr.qr_expires_in', ['count' => $countdown]) }}</span>
                </div>
            @else
                <div class="w-64 h-64 flex flex-col items-center justify-center text-gray-400">
                    <svg class="animate-spin h-8 w-8 text-emerald-600 mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ __('whatsapp-bridge-settings::messages.qr.qr_generating') }}</span>
                </div>
            @endif

            <button wire:click="disconnect" type="button" class="mt-6 px-4 py-2 text-sm font-medium text-red-600 hover:text-red-500 transition duration-150 ease-in-out">
                {{ __('whatsapp-bridge-settings::messages.qr.disconnect') }}
            </button>
        </div>
    @elseif ($status === 'connected')
        <div class="space-y-6">
            <div class="flex items-center justify-between p-6 bg-green-50 border border-green-200 rounded-2xl shadow-sm dark:bg-green-950/20 dark:border-green-800">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900/40 rounded-full flex items-center justify-center text-green-600 dark:text-green-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-md font-bold text-green-900 dark:text-green-200">{{ __('whatsapp-bridge-settings::messages.qr.connected_title') }}</h4>
                        @if ($connectedPhone)
                            <p class="text-sm text-green-700 dark:text-green-300 mt-1">{{ __('whatsapp-bridge-settings::messages.qr.connected_phone', ['phone' => $connectedPhone]) }}</p>
                        @endif
                    </div>
                </div>
                <button wire:click="disconnect" type="button" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 rounded-xl transition duration-150">
                    {{ __('whatsapp-bridge-settings::messages.qr.disconnect_button') }}
                </button>
            </div>

            <div class="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm space-y-4">
                <h3 class="text-md font-bold text-gray-800 dark:text-gray-200">{{ __('whatsapp-bridge-settings::messages.qr.test_section_title') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label for="testPhone" class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.qr.test_phone_label') }}</label>
                        <input id="testPhone" wire:model.defer="testPhone" type="text" placeholder="{{ __('whatsapp-bridge-settings::messages.qr.test_phone_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" />
                    </div>
                    <div class="space-y-1">
                        <label for="testMessage" class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.qr.test_message_label') }}</label>
                        <textarea id="testMessage" wire:model.defer="testMessage" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none resize-none"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 pt-2">
                    <button wire:click="sendTestMessage" type="button" @disabled($isSending) class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl shadow-sm hover:shadow transition duration-150 flex items-center gap-2 disabled:opacity-50">
                        @if ($isSending)
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ __('whatsapp-bridge-settings::messages.qr.test_sending') }}</span>
                        @else
                            <span>{{ __('whatsapp-bridge-settings::messages.qr.test_send_button') }}</span>
                        @endif
                    </button>

                    @if ($sendResult)
                        <div class="flex-1 max-w-md p-3 rounded-xl text-sm border {{ $sendSuccess ? 'bg-green-50 border-green-200 text-green-800 dark:bg-green-950/20 dark:border-green-900 dark:text-green-300' : 'bg-red-50 border-red-200 text-red-800 dark:bg-red-950/20 dark:border-red-900 dark:text-red-300' }}">
                            {{ $sendResult }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-300 dark:border-gray-700 rounded-2xl text-center">
            <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/20 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400 mb-4">
                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 448 512">
                    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ __('whatsapp-bridge-settings::messages.qr.connect_button') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs mb-6">{{ __('whatsapp-bridge-settings::messages.qr.description') }}</p>

            <button wire:click="generateQr" type="button" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl shadow transition duration-150 flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 448 512">
                    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                </svg>
                <span>{{ __('whatsapp-bridge-settings::messages.qr.connect_button') }}</span>
            </button>

            @if ($sendResult)
                <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm max-w-xs mx-auto">
                    {{ $sendResult }}
                </div>
            @endif
        </div>
    @endif
</div>

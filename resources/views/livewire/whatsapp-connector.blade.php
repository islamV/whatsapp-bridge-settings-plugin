<div class="mx-auto max-w-7xl space-y-6" dir="{{ str_starts_with(app()->getLocale(), 'ar') ? 'rtl' : 'ltr' }}">
    @if (session('whatsapp-settings-saved'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20 dark:text-emerald-300">
            {{ session('whatsapp-settings-saved') }}
        </div>
    @endif

    @php
        $tabIcons = [
            'general' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
            'bridge' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />',
            'meta' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />',
            'twilio' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />',
            'status' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />',
        ];

        $providerIcons = [
            'link' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />',
            'globe-alt' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />',
            'cloud' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />',
        ];

        $tabLabels = [
            'general' => __('whatsapp-bridge-settings::messages.tabs.general'),
            'bridge' => __('whatsapp-bridge-settings::messages.tabs.bridge'),
            'meta' => __('whatsapp-bridge-settings::messages.tabs.meta'),
            'twilio' => __('whatsapp-bridge-settings::messages.tabs.twilio'),
            'status' => __('whatsapp-bridge-settings::messages.tabs.status'),
        ];
    @endphp

    <div class="rounded-3xl border border-gray-200 bg-white/90 p-2 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
        <div class="flex flex-wrap gap-1">
        @foreach ($tabLabels as $key => $label)
            <button
                wire:click="$set('activeTab', '{{ $key }}')"
                type="button"
                class="flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium transition duration-150
                    {{ $activeTab === $key
                        ? 'bg-emerald-600 text-white shadow-sm'
                        : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200'
                    }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    {!! $tabIcons[$key] !!}
                </svg>
                {{ $label }}
            </button>
        @endforeach
        </div>
    </div>

    {{-- General Tab --}}
    @if ($activeTab === 'general')
        <div class="grid gap-6 xl:grid-cols-[1.35fr_0.95fr]">
            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600 dark:text-emerald-400">{{ __('whatsapp-bridge-settings::messages.general.select_provider') }}</p>
                        <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('whatsapp-bridge-settings::messages.page_heading') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.general.provider_hint') }}</p>
                    </div>

                    <div class="rounded-2xl bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {{ __('whatsapp-bridge-settings::messages.providers.' . $activeProvider . '.label') }}
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ($this->getProviders() as $key => $provider)
                        <button
                            wire:click="$set('activeProvider', '{{ $key }}')"
                            type="button"
                            class="group relative overflow-hidden rounded-2xl border p-4 text-start transition duration-200
                                {{ $activeProvider === $key
                                    ? 'border-emerald-500 bg-emerald-50/80 shadow-[0_10px_30px_rgba(16,185,129,0.14)] dark:border-emerald-400 dark:bg-emerald-950/30'
                                    : 'border-gray-200 bg-gray-50 hover:border-gray-300 hover:bg-white dark:border-gray-700 dark:bg-gray-800/70 dark:hover:border-gray-600'
                                }}"
                        >
                            @if ($activeProvider === $key)
                                <span class="absolute inset-x-0 top-0 h-1 bg-emerald-500"></span>
                                <span class="absolute end-4 top-4 inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            @endif

                            <div class="flex flex-col gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl
                                    {{ $activeProvider === $key ? 'bg-emerald-600 text-white' : 'bg-white text-gray-500 shadow-sm dark:bg-gray-900 dark:text-gray-300' }}">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        {!! $providerIcons[$provider['icon']] ?? '' !!}
                                    </svg>
                                </div>

                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $provider['label'] }}</div>
                                    <div class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $provider['description'] }}</div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">{{ __('whatsapp-bridge-settings::messages.general.settings') }}</p>
                    <h3 class="mt-2 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('whatsapp-bridge-settings::messages.general.controls_title') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.general.controls_description') }}</p>
                </div>

                <div class="space-y-3">
                    <label class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/70">
                        <input wire:model="otpEnabled" type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('whatsapp-bridge-settings::messages.general.otp_enabled') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.general.otp_enabled_help') }}</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/70">
                        <input wire:model="messagesEnabled" type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('whatsapp-bridge-settings::messages.general.messages_enabled') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.general.messages_enabled_help') }}</span>
                        </span>
                    </label>
                </div>

                <div class="mt-5 space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.general.otp_template') }}</label>
                    <textarea wire:model.defer="otpTemplate" rows="4" placeholder="{{ __('whatsapp-bridge-settings::messages.general.otp_template_placeholder') }}" class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.general.otp_template_helper') }}</p>
                </div>

                <div class="mt-6 flex justify-end">
                    <button wire:click="saveGeneral" type="button" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-500">
                        {{ __('whatsapp-bridge-settings::messages.general.save') }}
                    </button>
                </div>
            </section>
        </div>
    @endif

    {{-- Bridge Tab --}}
    @if ($activeTab === 'bridge')
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm space-y-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        {!! $providerIcons['link'] !!}
                    </svg>
                </div>
                <div>
                    <h3 class="text-md font-bold text-gray-800 dark:text-gray-200">{{ __('whatsapp-bridge-settings::messages.bridge.card_title') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.bridge.card_description') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.bridge.api_base_url') }}</label>
                    <input wire:model.defer="bridgeConfig.api_base_url" type="url" placeholder="{{ __('whatsapp-bridge-settings::messages.fields.api_base_url_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.bridge.api_token') }}</label>
                    <input wire:model.defer="bridgeConfig.api_token" type="password" placeholder="{{ __('whatsapp-bridge-settings::messages.bridge.api_token_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.bridge.sender') }}</label>
                    <input wire:model.defer="bridgeConfig.sender" type="text" placeholder="{{ __('whatsapp-bridge-settings::messages.bridge.sender_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.bridge.timeout') }}</label>
                    <input wire:model.defer="bridgeConfig.timeout" type="number" min="1" max="300" placeholder="30" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button wire:click="saveBridge" type="button" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-xl shadow-sm hover:shadow transition duration-150">
                    {{ __('whatsapp-bridge-settings::messages.general.save') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Meta Tab --}}
    @if ($activeTab === 'meta')
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm space-y-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        {!! $providerIcons['globe-alt'] !!}
                    </svg>
                </div>
                <div>
                    <h3 class="text-md font-bold text-gray-800 dark:text-gray-200">{{ __('whatsapp-bridge-settings::messages.meta.card_title') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.meta.card_description') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.meta.phone_number_id') }}</label>
                    <input wire:model.defer="metaConfig.phone_number_id" type="text" placeholder="{{ __('whatsapp-bridge-settings::messages.meta.phone_number_id_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.meta.access_token') }}</label>
                    <input wire:model.defer="metaConfig.access_token" type="password" placeholder="{{ __('whatsapp-bridge-settings::messages.meta.access_token_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.meta.business_account_id') }}</label>
                    <input wire:model.defer="metaConfig.business_account_id" type="text" placeholder="{{ __('whatsapp-bridge-settings::messages.meta.business_account_id_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.meta.verify_token') }}</label>
                    <input wire:model.defer="metaConfig.verify_token" type="text" placeholder="{{ __('whatsapp-bridge-settings::messages.meta.verify_token_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.meta.app_secret') }}</label>
                    <input wire:model.defer="metaConfig.app_secret" type="password" placeholder="{{ __('whatsapp-bridge-settings::messages.meta.app_secret_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.meta.timeout') }}</label>
                    <input wire:model.defer="metaConfig.timeout" type="number" min="1" max="300" placeholder="30" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button wire:click="saveMeta" type="button" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-medium rounded-xl shadow-sm hover:shadow transition duration-150">
                    {{ __('whatsapp-bridge-settings::messages.general.save') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Twilio Tab --}}
    @if ($activeTab === 'twilio')
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm space-y-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/40 rounded-full flex items-center justify-center text-red-600 dark:text-red-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        {!! $providerIcons['cloud'] !!}
                    </svg>
                </div>
                <div>
                    <h3 class="text-md font-bold text-gray-800 dark:text-gray-200">{{ __('whatsapp-bridge-settings::messages.twilio.card_title') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.twilio.card_description') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.twilio.account_sid') }}</label>
                    <input wire:model.defer="twilioConfig.account_sid" type="text" placeholder="{{ __('whatsapp-bridge-settings::messages.twilio.account_sid_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.twilio.auth_token') }}</label>
                    <input wire:model.defer="twilioConfig.auth_token" type="password" placeholder="{{ __('whatsapp-bridge-settings::messages.twilio.auth_token_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.twilio.from_number') }}</label>
                    <input wire:model.defer="twilioConfig.from_number" type="text" placeholder="{{ __('whatsapp-bridge-settings::messages.twilio.from_number_placeholder') }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ __('whatsapp-bridge-settings::messages.twilio.timeout') }}</label>
                    <input wire:model.defer="twilioConfig.timeout" type="number" min="1" max="300" placeholder="30" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button wire:click="saveTwilio" type="button" class="px-5 py-2.5 bg-red-600 hover:bg-red-500 text-white font-medium rounded-xl shadow-sm hover:shadow transition duration-150">
                    {{ __('whatsapp-bridge-settings::messages.general.save') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Status Tab --}}
    @if ($activeTab === 'status')
        @if ($status === 'waiting')
            <div wire:poll.3000ms="checkStatus" class="flex flex-col items-center justify-center p-6 border border-emerald-500 bg-white dark:bg-gray-800 rounded-2xl shadow-md max-w-sm mx-auto">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">{{ __('whatsapp-bridge-settings::messages.qr.qr_scan_title') }}</h3>

                @if ($qrCode)
                    <div class="w-64 h-64 bg-white p-3 border-2 border-emerald-500 rounded-xl shadow-inner flex items-center justify-center">
                        @if (str_starts_with($qrCode, 'data:image') || str_contains($qrCode, 'base64'))
                            <img src="{{ $qrCode }}" alt="{{ __('whatsapp-bridge-settings::messages.qr.qr_image_alt') }}" class="w-full h-full object-contain" />
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
    @endif
</div>

@php
    $initCc = $get('country_code') ?? '20';
    $initPhone = $get('test_phone') ?? '';
    $initMessage = $get('test_message') ?? '';
@endphp

<div
    x-data="{
        phone: @js($initPhone),
        countryCode: @js($initCc),
        message: @js($initMessage),
        appName: @js(config('app.name', 'WhatsApp Bridge')),
        currentTime: '',
        init() {
            this.updateTime();
            setInterval(() => this.updateTime(), 30000);

            const sync = () => {
                const wire = this.$wire;
                if (wire) {
                    const data = wire.mountedPageActionData
                        || wire.mountedActionData
                        || (wire.mountedActionsData ? wire.mountedActionsData[0] : null)
                        || wire.mountedTableActionData
                        || wire.data;

                    if (data) {
                        if (data.test_message !== undefined && data.test_message !== null) this.message = String(data.test_message);
                        if (data.country_code !== undefined && data.country_code !== null) this.countryCode = String(data.country_code);
                        if (data.test_phone !== undefined && data.test_phone !== null) this.phone = String(data.test_phone);
                    }
                }

                const modal = this.$el.closest('.fi-modal-window, .fi-modal, form, [role=\'dialog\']') || document.body;

                const textarea = modal.querySelector('textarea');
                if (textarea && textarea.value !== undefined && textarea.value.trim().length > 0) {
                    this.message = textarea.value;
                }

                const phoneEl = modal.querySelector('input[type=\'tel\'], input[placeholder*=\'1000000000\']');
                if (phoneEl && phoneEl.value !== undefined && phoneEl.value.trim().length > 0) {
                    this.phone = phoneEl.value;
                }
            };

            setInterval(sync, 150);
            sync();
        },
        updateTime() {
            const now = new Date();
            this.currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        },
        formatFullPhone() {
            const cc = String(this.countryCode || '20').replace(/[^0-9]/g, '');
            let rawPhone = String(this.phone || '').replace(/[^0-9]/g, '');

            if (!rawPhone) return '+' + cc + ' •••••••••';

            if (rawPhone.startsWith('0')) {
                rawPhone = rawPhone.substring(1);
            }

            if (rawPhone.startsWith(cc) && rawPhone.length > cc.length + 5) {
                return '+' + rawPhone;
            }

            return '+' + cc + ' ' + rawPhone;
        },
        renderMessageHtml(text) {
            if (!text) return '';
            const otpBadge = '<span style=\'font-weight:700;background:rgba(167,243,208,.8);color:#065f46;padding:1px 5px;border-radius:4px;font-family:monospace;font-size:12px;display:inline-block;\'>482731</span>';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\{otp\}/g, otpBadge);
        }
    }"
    class="w-full mt-2"
>
    {{-- WhatsApp Live Preview Header --}}
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            WhatsApp Message Preview
        </span>
        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-500/20">
            <span class="relative flex h-1.5 w-1.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
            </span>
            Live Preview
        </span>
    </div>

    {{-- WhatsApp Phone Card Container --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700/80 overflow-hidden shadow-sm bg-[#efeae2] dark:bg-[#0b141a]">
        {{-- App Header --}}
        <div class="bg-[#075e54] dark:bg-[#202c33] text-white px-3.5 py-2.5 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center font-bold text-xs text-white shrink-0 shadow-xs select-none">
                    WA
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-xs font-semibold text-white truncate leading-tight" x-text="appName"></h4>
                    <p class="text-[10px] text-emerald-100/80 truncate leading-tight flex items-center gap-1">
                        <span>Recipient:</span>
                        <span class="font-mono font-semibold text-white" x-text="formatFullPhone()"></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 opacity-70 shrink-0">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.07 1.18 2 2 0 012.03 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
            </div>
        </div>

        {{-- Date indicator --}}
        <div class="flex justify-center py-1.5 bg-[#efeae2] dark:bg-[#0b141a]">
            <span class="text-[10px] px-2.5 py-0.5 rounded-full font-medium bg-white/70 dark:bg-white/10 text-gray-500 dark:text-gray-400">
                Today
            </span>
        </div>

        {{-- Chat Body --}}
        <div class="px-3.5 pb-3.5 space-y-2 min-h-28 flex flex-col justify-end bg-[#efeae2] dark:bg-[#0b141a]">
            <template x-if="message && message.trim().length > 0">
                <div class="flex justify-end transition-all duration-200">
                    <div class="relative max-w-[85%] rounded-2xl rounded-tr-sm px-3 py-2.5 shadow-xs text-xs leading-relaxed bg-[#d9fdd3] dark:bg-[#005c4b] text-gray-900 dark:text-gray-100">
                        <div style="position:absolute;top:0;right:-6px;width:0;height:0;border-top:8px solid #d9fdd3;border-left:8px solid transparent;" class="dark:border-t-[#005c4b]"></div>
                        <div class="break-words whitespace-pre-wrap" x-html="renderMessageHtml(message)"></div>
                        <div class="flex items-center justify-end gap-1 mt-1 text-[10px] text-gray-500 dark:text-emerald-200/70">
                            <span x-text="currentTime"></span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#53bdeb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                                <polyline points="20 6 9 17 4 12" style="transform:translateX(-4px)"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="!message || message.trim().length === 0">
                <div class="text-center py-6 text-xs text-gray-400 dark:text-gray-500 italic">
                    Type a message or pick a template above to preview here…
                </div>
            </template>
        </div>
    </div>
</div>

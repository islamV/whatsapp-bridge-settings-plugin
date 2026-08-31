<div
    x-data="otpTemplateEditor({
        state: $wire.entangle('{{ $getStatePath() }}')
    })"
    class="w-full space-y-4"
>
    {{-- Section Label --}}
    <div class="flex items-center gap-2 pb-1 border-b border-gray-200/70 dark:border-gray-700/60">
        <div class="w-6 h-6 rounded-md bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center shrink-0">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
        </div>
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">OTP Message Template</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {{-- Left Side: Message Editor --}}
        <div class="lg:col-span-7 space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                        <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    Message Editor
                </label>
                <span x-text="(state || '').length + ' / 1024'" class="text-xs text-gray-400 dark:text-gray-500 font-mono tabular-nums"></span>
            </div>

            <div class="relative">
                <textarea
                    x-ref="editor"
                    x-model="state"
                    rows="5"
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800/90 text-gray-900 dark:text-gray-100 p-3.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-150 shadow-xs resize-y"
                    style="min-height: 110px;"
                    placeholder="Your verification code is: {otp}"
                ></textarea>
            </div>

            {{-- Insert Variable Chips --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-400 dark:text-gray-500">Variables:</span>
                <button
                    type="button"
                    @click="insertOtp()"
                    title="Click to insert {otp} at cursor position"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-mono font-semibold rounded-lg bg-violet-50 text-violet-700 border border-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:border-violet-500/20 hover:bg-violet-100 dark:hover:bg-violet-500/20 transition-all cursor-pointer active:scale-95 shadow-2xs"
                >
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    {otp}
                    <span class="font-sans font-normal opacity-70 text-[10px]">Verification code</span>
                </button>
            </div>

            {{-- Validation Indicators --}}
            <div>
                <template x-if="state && state.includes('{otp}')">
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        OTP variable included — your template is valid.
                    </div>
                </template>

                <template x-if="!state || !state.includes('{otp}')">
                    <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-xs text-amber-800 dark:text-amber-300">
                        <div class="flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            Template missing <code class="font-bold mx-0.5">{otp}</code> — the code won't be delivered.
                        </div>
                        <button
                            type="button"
                            @click="insertOtp()"
                            class="ml-3 shrink-0 text-amber-900 dark:text-amber-200 font-semibold underline hover:no-underline cursor-pointer text-[11px]"
                        >
                            Insert {otp}
                        </button>
                    </div>
                </template>
            </div>

            <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-relaxed">
                <code class="font-semibold text-gray-600 dark:text-gray-300">{otp}</code> is automatically replaced with the generated 6-digit code when the message is sent.
            </p>
        </div>

        {{-- Right Side: WhatsApp-style Live Preview --}}
        <div class="lg:col-span-5 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    WhatsApp Preview
                </span>
                <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-500/20">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                    </span>
                    Live
                </span>
            </div>

            {{-- WhatsApp Card Container --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700/80 overflow-hidden shadow-sm bg-[#efeae2] dark:bg-[#0b141a]">
                {{-- Header --}}
                <div class="bg-[#075e54] dark:bg-[#202c33] text-white px-3.5 py-2.5 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center font-bold text-xs text-white shrink-0 shadow-xs">
                        WA
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-semibold text-white truncate leading-tight">{{ config('app.name', 'WhatsApp OTP') }}</h4>
                        <p class="text-[10px] text-emerald-100/80 truncate leading-tight">Official Business Account</p>
                    </div>
                </div>

                {{-- Chat Body --}}
                <div class="p-3.5 space-y-3 min-h-[9rem] flex flex-col justify-end">
                    <template x-if="state && state.trim().length > 0">
                        <div class="flex justify-end transition-all duration-200">
                            <div class="relative bg-[#d9fdd3] dark:bg-[#005c4b] text-gray-900 dark:text-gray-100 p-3 rounded-2xl rounded-tr-sm shadow-xs max-w-[85%] text-xs leading-relaxed space-y-1">
                                <div class="break-words whitespace-pre-wrap" x-html="renderPreview(state)"></div>
                                <div class="flex items-center justify-end gap-1 text-[10px] text-gray-500 dark:text-emerald-200/70 pt-0.5">
                                    <span x-text="currentTime"></span>
                                    <span class="inline-flex items-center text-[#53bdeb]">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:-6px">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!state || state.trim().length === 0">
                        <div class="text-center py-6 text-xs text-gray-400 dark:text-gray-500 italic">
                            Type your OTP message to preview it here.
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function otpTemplateEditor(config) {
        return {
            state: config.state,
            fakeOtp: '482731',
            currentTime: '',
            init() {
                this.updateTime();
                setInterval(() => this.updateTime(), 30000);
            },
            updateTime() {
                const now = new Date();
                this.currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
            },
            insertOtp() {
                const el = this.$refs.editor;
                const current = this.state || '';
                if (!el) {
                    this.state = current + '{otp}';
                    return;
                }
                const start = el.selectionStart !== undefined ? el.selectionStart : current.length;
                const end = el.selectionEnd !== undefined ? el.selectionEnd : current.length;
                this.state = current.substring(0, start) + '{otp}' + current.substring(end);
                this.$nextTick(() => {
                    el.focus();
                    const newPos = start + 5;
                    el.setSelectionRange(newPos, newPos);
                });
            },
            renderPreview(val) {
                if (!val) return '';
                let escaped = String(val)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');

                const highlightedOtp = '<span class="font-bold text-gray-900 dark:text-white bg-emerald-200/80 dark:bg-emerald-600/50 px-1 py-0.5 rounded tracking-wide font-mono text-[13px] inline-block shadow-2xs">' + this.fakeOtp + '</span>';
                return escaped.replace(/\{otp\}/g, highlightedOtp);
            }
        };
    }
</script>

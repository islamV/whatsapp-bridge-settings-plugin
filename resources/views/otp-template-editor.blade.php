<div
    x-data="otpTemplateEditor({
        state: $wire.entangle('{{ $getStatePath() }}')
    })"
    class="w-full space-y-3"
>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Side: Message Editor -->
        <div class="lg:col-span-7 space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                    <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-400" />
                    <span>Message Editor</span>
                </label>
                <span x-text="(state || '').length + ' characters'" class="text-xs text-gray-400 dark:text-gray-500 font-mono"></span>
            </div>

            <div class="relative">
                <textarea
                    x-ref="editor"
                    x-model="state"
                    rows="5"
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800/90 text-gray-900 dark:text-gray-100 p-3.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-150 shadow-xs resize-y min-h-30"
                    placeholder="Your verification code is: {otp}"
                ></textarea>
            </div>

            <!-- Insert Variable & Chips -->
            <div class="flex flex-wrap items-center gap-2 pt-1">
                <button
                    type="button"
                    @click="insertOtp()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-emerald-500/30 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20 transition-all duration-150 active:scale-95 shadow-2xs cursor-pointer"
                >
                    <x-heroicon-m-plus class="w-3.5 h-3.5" />
                    <span>+ OTP Code</span>
                </button>

                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-gray-400 dark:text-gray-500">Available:</span>
                    <button
                        type="button"
                        @click="insertOtp()"
                        title="Click to insert {otp} at cursor position"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-mono font-medium rounded-md bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:border-purple-500/20 hover:bg-purple-100 dark:hover:bg-purple-500/20 transition-all cursor-pointer"
                    >
                        <code class="font-bold">{otp}</code>
                        <span class="text-[11px] font-sans opacity-80">Verification code</span>
                    </button>
                </div>
            </div>

            <!-- Validation Indicators -->
            <div class="pt-1">
                <template x-if="state && state.includes('{otp}')">
                    <div class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                        <x-heroicon-m-check class="w-4 h-4 shrink-0 text-emerald-500" />
                        <span>✓ OTP variable included</span>
                    </div>
                </template>

                <template x-if="!state || !state.includes('{otp}')">
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-xs text-amber-800 dark:text-amber-300">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-exclamation-triangle class="w-4 h-4 shrink-0 text-amber-500" />
                            <span>Your template does not contain the <code class="font-bold">{otp}</code> variable.</span>
                        </div>
                        <button
                            type="button"
                            @click="insertOtp()"
                            class="font-semibold text-amber-900 dark:text-amber-200 underline hover:no-underline cursor-pointer shrink-0 ml-2"
                        >
                            Insert {otp}
                        </button>
                    </div>
                </template>
            </div>

            <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-relaxed">
                <code class="font-semibold">{otp}</code> will automatically be replaced with the generated 6-digit verification code when the message is sent.
            </p>
        </div>

        <!-- Right Side: WhatsApp-style Live Preview -->
        <div class="lg:col-span-5 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">WhatsApp Preview</span>
                <span class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">Live</span>
            </div>

            <!-- WhatsApp Card Container -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700/80 overflow-hidden shadow-sm bg-[#efeae2] dark:bg-[#0b141a]">
                <!-- Header -->
                <div class="bg-[#075e54] dark:bg-[#202c33] text-white px-3.5 py-2.5 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center font-bold text-xs text-white shrink-0 shadow-xs">
                        WA
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-semibold text-white truncate leading-tight">{{ config('app.name', 'WhatsApp OTP') }}</h4>
                        <p class="text-[10px] text-emerald-100/80 truncate leading-tight">Official Business Account</p>
                    </div>
                </div>

                <!-- Chat Body -->
                <div class="p-3.5 space-y-3 min-h-40 flex flex-col justify-end">
                    <template x-if="state && state.trim().length > 0">
                        <div class="flex justify-end transition-all duration-200">
                            <div class="relative bg-[#d9fdd3] dark:bg-[#005c4b] text-gray-900 dark:text-gray-100 p-3 rounded-2xl rounded-tr-xs shadow-xs max-w-[85%] text-xs leading-relaxed space-y-1">
                                <div class="wrap-break-word whitespace-pre-wrap" x-html="renderPreview(state)"></div>
                                <div class="flex items-center justify-end gap-1 text-[10px] text-gray-500 dark:text-emerald-200/70 pt-0.5">
                                    <span x-text="currentTime"></span>
                                    <x-heroicon-m-check class="w-3.5 h-3.5 text-[#53bdeb]" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!state || state.trim().length === 0">
                        <div class="text-center py-8 text-xs text-gray-400 dark:text-gray-500 italic">
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

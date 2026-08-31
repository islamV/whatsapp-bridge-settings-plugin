{{-- ═══════════════════════════════════════════════════════════════
     OTP Template Editor — Premium UI
     ═══════════════════════════════════════════════════════════════ --}}
<div
    x-data="otpTemplateEditor({
        state: $wire.entangle('{{ $getStatePath() }}'),
        appName: @js(config('app.name', 'My App'))
    })"
    class="w-full"
>
    {{-- ── Section header ─────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-gray-100 dark:border-white/5">
        <div class="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center shadow-sm shrink-0">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-none">OTP Message Template</p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Customize the message sent with every one-time password</p>
        </div>
    </div>

    {{-- ── Two-column layout ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- ── LEFT: editor panel ─────────────────────────────────────── --}}
        <div class="lg:col-span-7 space-y-4">

            {{-- Textarea card --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-gray-900/60 shadow-sm overflow-hidden">

                {{-- Card toolbar --}}
                <div class="flex items-center justify-between px-3.5 py-2.5 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700/60">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                        Message
                    </span>
                    <span
                        x-text="(state || '').length + ' / 1024'"
                        class="text-[11px] font-mono tabular-nums text-gray-400 dark:text-gray-500"
                        :class="(state || '').length > 900 ? 'text-amber-500' : ''"
                    ></span>
                </div>

                {{-- Textarea --}}
                <textarea
                    x-ref="editor"
                    x-model="state"
                    rows="5"
                    class="w-full bg-transparent text-gray-900 dark:text-gray-100 p-3.5 text-sm focus:outline-none resize-y border-0 ring-0 focus:ring-0"
                    style="min-height: 120px;"
                    placeholder="Your verification code is: {otp}"
                ></textarea>
            </div>

            {{-- ── Variables row ─────────────────────────────────────── --}}
            <div class="space-y-2.5">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Insert Variable</p>

                <div class="flex flex-wrap items-center gap-2">

                    {{-- {otp} chip --}}
                    <button
                        type="button"
                        @click="insertToken('{otp}')"
                        title="Insert {otp} at cursor"
                        class="group inline-flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-full border border-violet-200 dark:border-violet-500/30 bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-300 text-xs font-semibold hover:bg-violet-100 dark:hover:bg-violet-500/20 hover:border-violet-300 dark:hover:border-violet-400/40 active:scale-95 transition-all duration-150 shadow-xs cursor-pointer select-none"
                    >
                        <span class="w-4 h-4 rounded-full bg-violet-200 dark:bg-violet-500/30 flex items-center justify-center shrink-0">
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </span>
                        <span class="font-mono">{otp}</span>
                        <span class="opacity-50 font-normal text-[10px] font-sans">code</span>
                    </button>

                    {{-- Custom variable chips --}}
                    <template x-for="v in customVars" :key="v.name">
                        <span class="group inline-flex items-center gap-0 rounded-full border border-sky-200 dark:border-sky-500/30 bg-sky-50 dark:bg-sky-500/10 shadow-xs overflow-hidden">
                            <button
                                type="button"
                                @click="insertToken('{' + v.name + '}')"
                                :title="'Insert {' + v.name + '}'"
                                class="inline-flex items-center gap-2 pl-2 pr-2 py-1.5 text-xs font-semibold text-sky-700 dark:text-sky-300 hover:text-sky-900 dark:hover:text-sky-100 transition-colors cursor-pointer select-none"
                            >
                                <span class="w-4 h-4 rounded-full bg-sky-200 dark:bg-sky-500/30 flex items-center justify-center shrink-0">
                                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                </span>
                                <span class="font-mono" x-text="'{' + v.name + '}'"></span>
                                <span class="opacity-50 font-normal text-[10px] font-sans" x-text="v.preview"></span>
                            </button>
                            <button
                                type="button"
                                @click="removeVar(v.name)"
                                :title="'Remove {' + v.name + '}'"
                                class="flex items-center justify-center w-5 h-5 mr-1 rounded-full text-sky-400 hover:bg-rose-100 dark:hover:bg-rose-500/20 hover:text-rose-500 dark:hover:text-rose-400 transition-all cursor-pointer"
                            >
                                <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </span>
                    </template>

                    {{-- Add variable button --}}
                    <button
                        type="button"
                        @click="showAddVarForm = !showAddVarForm"
                        :class="showAddVarForm
                            ? 'border-emerald-300 dark:border-emerald-500/40 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                            : 'border-gray-200 dark:border-gray-600 text-gray-400 dark:text-gray-500 hover:border-emerald-300 hover:text-emerald-600 dark:hover:text-emerald-400 dark:hover:border-emerald-500/40'"
                        class="inline-flex items-center gap-1.5 pl-2 pr-3 py-1.5 rounded-full border border-dashed text-xs font-medium transition-all duration-150 cursor-pointer select-none"
                    >
                        <span class="w-4 h-4 rounded-full border border-current flex items-center justify-center shrink-0 transition-transform duration-200" :class="showAddVarForm ? 'rotate-45' : ''">
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </span>
                        <span x-text="showAddVarForm ? 'Cancel' : 'Add variable'"></span>
                    </button>
                </div>

                {{-- ── Add variable form ─────────────────────────────── --}}
                <div
                    x-show="showAddVarForm"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                    class="rounded-xl border border-gray-200 dark:border-gray-700/60 bg-white dark:bg-gray-900/60 shadow-sm overflow-hidden"
                >
                    {{-- Form header --}}
                    <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-700/60 flex items-center gap-2">
                        <div class="w-5 h-5 rounded-md bg-sky-100 dark:bg-sky-500/20 flex items-center justify-center">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#0ea5e9" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">New Variable</span>
                    </div>

                    {{-- Form body --}}
                    <div class="p-4 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            {{-- Name --}}
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">
                                    Variable Name
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 font-mono text-xs pointer-events-none select-none">{</span>
                                    <input
                                        type="text"
                                        x-model="newVarName"
                                        @keydown.enter.prevent="addVar()"
                                        placeholder="name"
                                        :class="varNameConflict ? 'border-rose-300 dark:border-rose-500/40 focus:ring-rose-500 focus:border-rose-500' : 'border-gray-200 dark:border-gray-700 focus:ring-emerald-500 focus:border-emerald-500'"
                                        class="w-full rounded-lg border bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 pl-6 pr-6 py-2 text-xs font-mono focus:ring-2 transition-all"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 font-mono text-xs pointer-events-none select-none">}</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1" x-show="newVarName.trim() && !varNameConflict">
                                    Inserts as <code class="font-mono font-semibold" x-text="'{' + newVarName.trim().replace(/[^a-zA-Z0-9_]/g,'').toLowerCase() + '}'"></code>
                                </p>
                                <p class="text-[10px] text-rose-500 dark:text-rose-400 mt-1 font-medium" x-show="varNameConflict">
                                    Name already in use.
                                </p>
                            </div>

                            {{-- Preview value --}}
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">
                                    Preview Value
                                </label>
                                <input
                                    type="text"
                                    x-model="newVarPreview"
                                    @keydown.enter.prevent="addVar()"
                                    placeholder="e.g. John"
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                                />
                                <p class="text-[10px] text-gray-400 mt-1">Shown in live preview only</p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-1">
                            <button
                                type="button"
                                @click="addVar()"
                                :disabled="!newVarName.trim() || !newVarPreview.trim() || varNameConflict"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer shadow-xs"
                            >
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Add Variable
                            </button>
                            <button
                                type="button"
                                @click="showAddVarForm = false; newVarName = ''; newVarPreview = ''"
                                class="px-4 py-2 text-xs font-medium rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all cursor-pointer"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Validation banner ─────────────────────────────────── --}}
            <div>
                <template x-if="state && state.includes('{otp}')">
                    <div class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                        <div class="w-4 h-4 rounded-full bg-emerald-200 dark:bg-emerald-500/30 flex items-center justify-center shrink-0">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        OTP variable included — your template is valid.
                    </div>
                </template>
                <template x-if="!state || !state.includes('{otp}')">
                    <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-xs text-amber-800 dark:text-amber-300">
                        <div class="flex items-center gap-2.5">
                            <div class="w-4 h-4 rounded-full bg-amber-200 dark:bg-amber-500/30 flex items-center justify-center shrink-0">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </div>
                            Missing <code class="font-bold font-mono mx-0.5 bg-amber-100 dark:bg-amber-500/20 px-1 rounded">{otp}</code> — code won't be delivered.
                        </div>
                        <button
                            type="button"
                            @click="insertToken('{otp}')"
                            class="ml-3 shrink-0 px-2.5 py-1 rounded-lg bg-amber-200/60 dark:bg-amber-500/20 text-amber-900 dark:text-amber-200 font-semibold hover:bg-amber-200 dark:hover:bg-amber-500/30 transition-all cursor-pointer text-[11px]"
                        >
                            Insert {otp}
                        </button>
                    </div>
                </template>
            </div>

            {{-- Help text --}}
            <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-relaxed">
                <code class="font-mono font-semibold text-gray-500 dark:text-gray-400">{otp}</code> is replaced with the 6-digit code at send time.
                Custom variables are replaced by values passed to <code class="font-mono font-semibold">sendOtp()</code>.
            </p>
        </div>

        {{-- ── RIGHT: WhatsApp live preview ───────────────────────────── --}}
        <div class="lg:col-span-5 space-y-3">

            {{-- Preview header --}}
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    Live Preview
                </span>
                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-200 dark:border-emerald-500/20">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                    </span>
                    Live
                </span>
            </div>

            {{-- WhatsApp mock shell --}}
            <div class="rounded-2xl border border-gray-200/80 dark:border-gray-700/60 overflow-hidden shadow-lg" style="background:#efeae2;">
                {{-- Dark-mode bg --}}
                <div class="dark:hidden">
                    {{-- WA Header light --}}
                    <div class="flex items-center gap-3 px-4 py-3" style="background:#075e54;">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs text-white shrink-0 select-none shadow" style="background:linear-gradient(135deg,#25d366,#128c7e);">
                            WA
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-white truncate leading-tight" x-text="appName"></h4>
                            <p class="text-[10px] leading-tight" style="color:rgba(255,255,255,.65);">Official Business Account</p>
                        </div>
                        <div class="flex items-center gap-3 opacity-60">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.07 1.18 2 2 0 012.03 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                        </div>
                    </div>
                    {{-- Subtle date chip --}}
                    <div class="flex justify-center py-2">
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full font-medium" style="background:rgba(255,255,255,.7);color:#6b7280;">Today</span>
                    </div>
                    {{-- Chat body --}}
                    <div class="px-3 pb-4 space-y-2 min-h-32 flex flex-col justify-end" style="background:#efeae2;">
                        <template x-if="state && state.trim().length > 0">
                            <div class="flex justify-end">
                                <div class="relative max-w-[85%] rounded-2xl rounded-tr-sm px-3 py-2.5 shadow-sm text-xs leading-relaxed" style="background:#d9fdd3;color:#111;">
                                    {{-- Bubble tail --}}
                                    <div style="position:absolute;top:0;right:-6px;width:0;height:0;border-top:8px solid #d9fdd3;border-left:8px solid transparent;"></div>
                                    <div class="break-words whitespace-pre-wrap" x-html="renderPreview(state)"></div>
                                    <div class="flex items-center justify-end gap-1 mt-1" style="color:#8696a0;font-size:10px;">
                                        <span x-text="currentTime"></span>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#53bdeb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/><polyline points="20 6 9 17 4 12" style="transform:translateX(-4px)"/></svg>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!state || state.trim().length === 0">
                            <div class="text-center py-8 text-xs italic" style="color:#aaa;">
                                Start typing to preview your message…
                            </div>
                        </template>
                    </div>
                    {{-- Input bar --}}
                    <div class="flex items-center gap-2 px-3 py-2.5 border-t" style="background:#f0f2f5;border-color:#e9edef;">
                        <div class="flex-1 rounded-full px-3.5 py-1.5 text-xs" style="background:#fff;color:#aaa;">Type a message</div>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background:#00a884;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4 20-7z"/></svg>
                        </div>
                    </div>
                </div>

                {{-- Dark mode variant --}}
                <div class="hidden dark:block" style="background:#0b141a;">
                    <div class="flex items-center gap-3 px-4 py-3" style="background:#202c33;">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs text-white shrink-0 select-none shadow" style="background:linear-gradient(135deg,#25d366,#128c7e);">
                            WA
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-white truncate leading-tight" x-text="appName"></h4>
                            <p class="text-[10px] leading-tight" style="color:rgba(255,255,255,.45);">Official Business Account</p>
                        </div>
                        <div class="flex items-center gap-3 opacity-40">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.07 1.18 2 2 0 012.03 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                        </div>
                    </div>
                    <div class="flex justify-center py-2" style="background:#0b141a;">
                        <span class="text-[10px] px-2.5 py-0.5 rounded-full font-medium" style="background:rgba(255,255,255,.08);color:#8696a0;">Today</span>
                    </div>
                    <div class="px-3 pb-4 space-y-2 min-h-32 flex flex-col justify-end" style="background:#0b141a;">
                        <template x-if="state && state.trim().length > 0">
                            <div class="flex justify-end">
                                <div class="relative max-w-[85%] rounded-2xl rounded-tr-sm px-3 py-2.5 shadow-sm text-xs leading-relaxed" style="background:#005c4b;color:#e9edef;">
                                    <div style="position:absolute;top:0;right:-6px;width:0;height:0;border-top:8px solid #005c4b;border-left:8px solid transparent;"></div>
                                    <div class="break-words whitespace-pre-wrap" x-html="renderPreview(state)"></div>
                                    <div class="flex items-center justify-end gap-1 mt-1" style="color:rgba(233,238,221,.5);font-size:10px;">
                                        <span x-text="currentTime"></span>
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#53bdeb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/><polyline points="20 6 9 17 4 12" style="transform:translateX(-4px)"/></svg>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!state || state.trim().length === 0">
                            <div class="text-center py-8 text-xs italic" style="color:#8696a0;">
                                Start typing to preview your message…
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-2.5 border-t" style="background:#202c33;border-color:#313d45;">
                        <div class="flex-1 rounded-full px-3.5 py-1.5 text-xs" style="background:#2a3942;color:#8696a0;">Type a message</div>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background:#00a884;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4 20-7z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Variable legend --}}
            <template x-if="customVars.length > 0">
                <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-800/40 p-3 space-y-1.5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">Preview Values</p>
                    <div class="flex flex-wrap gap-1.5">
                        {{-- OTP always shown --}}
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/20 text-[10px]">
                            <code class="font-mono font-bold text-violet-600 dark:text-violet-400">{otp}</code>
                            <span class="text-gray-300 dark:text-gray-600">→</span>
                            <code class="font-mono font-semibold text-gray-600 dark:text-gray-300">482731</code>
                        </span>
                        <template x-for="v in customVars" :key="v.name">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 text-[10px]">
                                <code class="font-mono font-bold text-sky-600 dark:text-sky-400" x-text="'{' + v.name + '}'"></code>
                                <span class="text-gray-300 dark:text-gray-600">→</span>
                                <span class="font-medium text-gray-600 dark:text-gray-300" x-text="v.preview"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </template>
        </div>

    </div>
</div>

<script>
    function otpTemplateEditor(config) {
        const STORAGE_KEY = 'otp_template_custom_vars';

        return {
            state:          config.state,
            appName:        config.appName,
            fakeOtp:        '482731',
            currentTime:    '',
            showAddVarForm: false,
            newVarName:     '',
            newVarPreview:  '',
            customVars:     [],

            get varNameConflict() {
                const n = this.newVarName.trim().replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
                return n.length > 0 && (n === 'otp' || this.customVars.some(v => v.name === n));
            },

            init() {
                this.updateTime();
                setInterval(() => this.updateTime(), 30000);
                try {
                    const s = localStorage.getItem(STORAGE_KEY);
                    if (s) this.customVars = JSON.parse(s);
                } catch (_) {}
            },

            updateTime() {
                const d = new Date();
                this.currentTime = d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
            },

            insertToken(token) {
                const el = this.$refs.editor, cur = this.state || '';
                if (!el) { this.state = cur + token; return; }
                const s = el.selectionStart ?? cur.length, e = el.selectionEnd ?? cur.length;
                this.state = cur.slice(0, s) + token + cur.slice(e);
                this.$nextTick(() => { el.focus(); el.setSelectionRange(s + token.length, s + token.length); });
            },
            insertOtp() { this.insertToken('{otp}'); },

            addVar() {
                const name = this.newVarName.trim().replace(/[^a-zA-Z0-9_]/g,'').toLowerCase();
                const preview = this.newVarPreview.trim();
                if (!name || !preview || this.varNameConflict) return;
                this.customVars.push({ name, preview });
                this._save();
                this.newVarName = this.newVarPreview = '';
                this.showAddVarForm = false;
            },
            removeVar(name) { this.customVars = this.customVars.filter(v => v.name !== name); this._save(); },
            _save() { try { localStorage.setItem(STORAGE_KEY, JSON.stringify(this.customVars)); } catch(_){} },

            renderPreview(val) {
                if (!val) return '';
                let o = String(val)
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

                // {otp}
                o = o.replace(/\{otp\}/g,
                    '<span style="font-weight:800;background:#bbf7d0;color:#065f46;padding:0 5px 1px;border-radius:5px;font-family:monospace;font-size:13px;display:inline-block;letter-spacing:.5px;">'
                    + this.fakeOtp + '</span>');

                // custom vars
                for (const v of this.customVars) {
                    const safe = v.name.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
                    o = o.replace(new RegExp('\\{'+safe+'\\}','g'),
                        '<span style="font-weight:700;background:#bfdbfe;color:#1e40af;padding:0 4px 1px;border-radius:4px;font-size:11px;display:inline-block;">'
                        + this._esc(v.preview) + '</span>');
                }
                return o;
            },
            _esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); },
        };
    }
</script>

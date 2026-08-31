<div
    x-data="otpTemplateEditor({
        state: $wire.entangle('{{ $getStatePath() }}'),
        appName: @js(config('app.name', 'My App'))
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

            {{-- Char counter + label --}}
            <div class="flex items-center justify-between">
                <label class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                        <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    Message Editor
                </label>
                <span x-text="(state || '').length + ' / 1024'" class="text-xs text-gray-400 dark:text-gray-500 font-mono tabular-nums"></span>
            </div>

            {{-- Textarea --}}
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

            {{-- Variables bar --}}
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">Variables:</span>

                    {{-- Built-in {otp} chip --}}
                    <button
                        type="button"
                        @click="insertToken('{otp}')"
                        title="Insert {otp} at cursor"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-mono font-semibold rounded-lg bg-violet-50 text-violet-700 border border-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:border-violet-500/20 hover:bg-violet-100 dark:hover:bg-violet-500/20 transition-all cursor-pointer active:scale-95 shadow-2xs"
                    >
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        {otp}
                        <span class="font-sans font-normal opacity-60 text-[10px]">Verification code</span>
                    </button>

                    {{-- Custom variable chips --}}
                    <template x-for="v in customVars" :key="v.name">
                        <span class="inline-flex items-center gap-1 rounded-lg border border-blue-200 dark:border-blue-500/20 bg-blue-50 dark:bg-blue-500/10">
                            <button
                                type="button"
                                @click="insertToken('{' + v.name + '}')"
                                :title="'Insert {' + v.name + '} — preview: ' + v.preview"
                                class="inline-flex items-center gap-1.5 pl-2.5 pr-1 py-1 text-xs font-mono font-semibold text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 transition-colors cursor-pointer"
                            >
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                <span x-text="'{' + v.name + '}'"></span>
                                <span class="font-sans font-normal opacity-60 text-[10px]" x-text="v.preview"></span>
                            </button>
                            {{-- Remove button --}}
                            <button
                                type="button"
                                @click="removeVar(v.name)"
                                :title="'Remove {' + v.name + '}'"
                                class="pr-1.5 py-1 text-blue-400 hover:text-rose-500 dark:hover:text-rose-400 transition-colors cursor-pointer"
                            >
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </span>
                    </template>

                    {{-- Add variable toggle --}}
                    <button
                        type="button"
                        @click="showAddVarForm = !showAddVarForm"
                        class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium rounded-lg border border-dashed border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 hover:border-primary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-all cursor-pointer"
                        :class="showAddVarForm ? 'border-primary-400 text-primary-600 dark:text-primary-400' : ''"
                    >
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Add variable
                    </button>
                </div>

                {{-- Add variable form --}}
                <div
                    x-show="showAddVarForm"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="flex flex-wrap items-end gap-2 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex-1 min-w-28">
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Variable name</label>
                        <input
                            type="text"
                            x-model="newVarName"
                            @keydown.enter.prevent="addVar()"
                            placeholder="e.g. name"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2.5 py-1.5 text-xs font-mono focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all"
                        />
                        <p class="text-[10px] text-gray-400 mt-0.5">Used as <code class="font-semibold">{name}</code> in template</p>
                    </div>
                    <div class="flex-1 min-w-28">
                        <label class="block text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Preview value</label>
                        <input
                            type="text"
                            x-model="newVarPreview"
                            @keydown.enter.prevent="addVar()"
                            placeholder="e.g. John"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all"
                        />
                        <p class="text-[10px] text-gray-400 mt-0.5">Shown in the preview only</p>
                    </div>
                    <div class="flex gap-2 pb-5">
                        <button
                            type="button"
                            @click="addVar()"
                            :disabled="!newVarName.trim() || !newVarPreview.trim()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-primary-600 text-white hover:bg-primary-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer"
                        >
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Add
                        </button>
                        <button
                            type="button"
                            @click="showAddVarForm = false; newVarName = ''; newVarPreview = ''"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all cursor-pointer"
                        >
                            Cancel
                        </button>
                    </div>
                    {{-- Duplicate name warning --}}
                    <template x-if="varNameConflict">
                        <p class="w-full text-[11px] text-rose-600 dark:text-rose-400 font-medium">
                            A variable with this name already exists.
                        </p>
                    </template>
                </div>
            </div>

            {{-- Validation --}}
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
                        <button type="button" @click="insertToken('{otp}')" class="ml-3 shrink-0 text-amber-900 dark:text-amber-200 font-semibold underline hover:no-underline cursor-pointer text-[11px]">
                            Insert {otp}
                        </button>
                    </div>
                </template>
            </div>

            <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-relaxed">
                <code class="font-semibold text-gray-600 dark:text-gray-300">{otp}</code> is replaced with the generated 6-digit code at send time. Custom variables are replaced by values you pass when calling <code class="font-semibold">sendOtp()</code>.
            </p>
        </div>

        {{-- Right Side: WhatsApp Live Preview --}}
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

            {{-- WhatsApp mock --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700/80 overflow-hidden shadow-sm bg-[#efeae2] dark:bg-[#0b141a]">
                {{-- Header --}}
                <div class="bg-[#075e54] dark:bg-[#202c33] text-white px-3.5 py-2.5 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center font-bold text-xs text-white shrink-0 shadow-xs select-none">
                        WA
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-semibold text-white truncate leading-tight" x-text="appName"></h4>
                        <p class="text-[10px] text-emerald-100/80 truncate leading-tight">Official Business Account</p>
                    </div>
                </div>

                {{-- Chat body --}}
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

            {{-- Custom vars preview legend --}}
            <template x-if="customVars.length > 0">
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <template x-for="v in customVars" :key="v.name">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-[10px]">
                            <code class="font-mono font-semibold text-blue-700 dark:text-blue-300" x-text="'{' + v.name + '}'"></code>
                            <span class="text-gray-400">→</span>
                            <span class="text-gray-600 dark:text-gray-400 font-medium" x-text="v.preview"></span>
                        </span>
                    </template>
                </div>
            </template>
        </div>

    </div>
</div>

<script>
    function otpTemplateEditor(config) {
        const STORAGE_KEY = 'otp_template_custom_vars';

        return {
            state:           config.state,
            appName:         config.appName,
            fakeOtp:         '482731',
            currentTime:     '',
            showAddVarForm:  false,
            newVarName:      '',
            newVarPreview:   '',
            customVars:      [],

            // ── Computed ──────────────────────────────────────────────────────
            get varNameConflict() {
                const name = this.newVarName.trim().replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
                return name.length > 0 && (
                    name === 'otp' ||
                    this.customVars.some(v => v.name === name)
                );
            },

            // ── Lifecycle ─────────────────────────────────────────────────────
            init() {
                this.updateTime();
                setInterval(() => this.updateTime(), 30000);

                // Load persisted custom vars
                try {
                    const stored = localStorage.getItem(STORAGE_KEY);
                    if (stored) this.customVars = JSON.parse(stored);
                } catch (_) {}
            },

            // ── Time ──────────────────────────────────────────────────────────
            updateTime() {
                const now = new Date();
                this.currentTime =
                    now.getHours().toString().padStart(2, '0') + ':' +
                    now.getMinutes().toString().padStart(2, '0');
            },

            // ── Token insertion ───────────────────────────────────────────────
            insertToken(token) {
                const el      = this.$refs.editor;
                const current = this.state || '';
                if (!el) { this.state = current + token; return; }

                const start = el.selectionStart ?? current.length;
                const end   = el.selectionEnd   ?? current.length;
                this.state  = current.substring(0, start) + token + current.substring(end);

                this.$nextTick(() => {
                    el.focus();
                    const pos = start + token.length;
                    el.setSelectionRange(pos, pos);
                });
            },

            // Keep old name for any external callers
            insertOtp() { this.insertToken('{otp}'); },

            // ── Custom variables ──────────────────────────────────────────────
            addVar() {
                const name    = this.newVarName.trim().replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
                const preview = this.newVarPreview.trim();

                if (!name || !preview || this.varNameConflict) return;

                this.customVars.push({ name, preview });
                this._persistVars();

                this.newVarName    = '';
                this.newVarPreview = '';
                this.showAddVarForm = false;
            },

            removeVar(name) {
                this.customVars = this.customVars.filter(v => v.name !== name);
                this._persistVars();
            },

            _persistVars() {
                try { localStorage.setItem(STORAGE_KEY, JSON.stringify(this.customVars)); } catch (_) {}
            },

            // ── Preview renderer ──────────────────────────────────────────────
            renderPreview(val) {
                if (!val) return '';

                let out = String(val)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');

                // {otp} → green badge
                const otpBadge =
                    '<span style="font-weight:700;background:rgba(167,243,208,.8);color:#111;' +
                    'padding:1px 5px;border-radius:4px;font-family:monospace;font-size:13px;display:inline-block;">' +
                    this.fakeOtp + '</span>';
                out = out.replace(/\{otp\}/g, otpBadge);

                // Custom variables → blue badge
                for (const v of this.customVars) {
                    const badge =
                        '<span style="font-weight:600;background:rgba(219,234,254,.9);color:#1d4ed8;' +
                        'padding:1px 4px;border-radius:4px;font-size:11px;display:inline-block;">' +
                        this._escapeHtml(v.preview) + '</span>';
                    const regex = new RegExp('\\{' + v.name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\}', 'g');
                    out = out.replace(regex, badge);
                }

                return out;
            },

            _escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            },
        };
    }
</script>

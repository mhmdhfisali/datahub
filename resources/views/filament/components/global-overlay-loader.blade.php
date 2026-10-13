<div x-data="globalLoaderComponent()"
     x-cloak
     x-show="showLoader"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-gray-950 select-none pointer-events-auto"
     style="display: none;"
     aria-live="assertive"
     role="alert">

    {{-- Main Container Berada di Ruang Gelap Solid --}}
    <div class="relative flex flex-col items-center justify-center p-8 max-w-sm w-full mx-4 text-center">

        {{-- Background Radial Glow Effect --}}
        <div class="absolute -inset-4 bg-gradient-to-r from-indigo-500/20 via-purple-500/20 to-cyan-500/20 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Orbiting Particles & Nodes (Muter Berbentuk Data) --}}
        <div class="relative w-44 h-44 flex items-center justify-center mb-6">
            
            {{-- Outer Orbit Ring (Berputar Searah Jarum Jam) --}}
            <svg class="absolute inset-0 w-full h-full animate-[spin_6s_linear_infinite]" viewBox="0 0 160 160" fill="none">
                <circle cx="80" cy="80" r="72" stroke="rgba(99, 102, 241, 0.35)" stroke-width="1.5" stroke-dasharray="8 6" />
                {{-- Data Node Particle 1 --}}
                <circle cx="80" cy="8" r="4.5" fill="#6366f1" />
                <circle cx="80" cy="8" r="8" fill="#6366f1" fill-opacity="0.3" />
                {{-- Data Node Particle 2 --}}
                <circle cx="80" cy="152" r="3.5" fill="#818cf8" />
            </svg>

            {{-- Middle Orbit Ring (Berputar Berlawanan Arah) --}}
            <svg class="absolute inset-3 w-[136px] h-[136px] animate-[spin_4s_linear_infinite_reverse]" viewBox="0 0 136 136" fill="none">
                <circle cx="68" cy="68" r="60" stroke="rgba(168, 85, 247, 0.4)" stroke-width="1.5" stroke-dasharray="5 5" />
                {{-- Data Node Particle 3 --}}
                <circle cx="128" cy="68" r="4" fill="#a855f7" />
                <circle cx="128" cy="68" r="7" fill="#a855f7" fill-opacity="0.3" />
                {{-- Data Node Particle 4 --}}
                <circle cx="8" cy="68" r="3" fill="#c084fc" />
            </svg>

            {{-- Inner Orbit Ring (Berputar Cepat Searah Jarum Jam) --}}
            <svg class="absolute inset-7 w-[104px] h-[104px] animate-[spin_3s_linear_infinite]" viewBox="0 0 104 104" fill="none">
                <circle cx="52" cy="52" r="44" stroke="rgba(6, 182, 212, 0.45)" stroke-width="1" stroke-dasharray="4 4" />
                {{-- Data Node Particle 5 --}}
                <circle cx="52" cy="8" r="3" fill="#06b6d4" />
                <circle cx="52" cy="8" r="6" fill="#06b6d4" fill-opacity="0.3" />
            </svg>

            {{-- Center Pulsing 3D Database Node --}}
            <div class="relative z-10 flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 text-white shadow-2xl shadow-indigo-500/50 border border-indigo-400/40 animate-pulse">
                {{-- Database Stack Icon --}}
                <svg class="w-8 h-8 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
        </div>

        {{-- Branding ASETS & Enterprise Title --}}
        <div class="space-y-1.5 mb-5">
            <h2 class="text-2xl font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-200 to-indigo-400 drop-shadow-[0_2px_12px_rgba(99,102,241,0.6)]">
                ASETS
            </h2>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-indigo-500/15 border border-indigo-500/30 text-[10px] font-bold uppercase tracking-widest text-indigo-400">
                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping"></span>
                <span>Enterprise Data Hub</span>
            </div>
        </div>

        {{-- Dynamic Status Loading Text --}}
        <div class="flex items-center justify-center gap-2 text-xs font-medium text-gray-300 min-h-[24px]">
            <svg class="w-3.5 h-3.5 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="loadingMessage" class="tracking-wide text-gray-200"></span>
        </div>

        {{-- Subtle Progress Bar Barisan Bawah --}}
        <div class="w-36 h-1 bg-gray-900 rounded-full mt-4 overflow-hidden border border-gray-800">
            <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-400 rounded-full animate-[progress_1.6s_ease-in-out_infinite]"></div>
        </div>
    </div>
</div>

<script>
    function globalLoaderComponent() {
        return {
            showLoader: false,
            loadingMessage: 'Memuat database...',
            messages: [
                'Memuat database...',
                'Memparsing skema baris...',
                'Menyiapkan visualisasi...',
                'Mengoptimasi metrik data...',
                'Menghubungkan pipeline...'
            ],
            messageIndex: 0,
            timer: null,

            startLoading() {
                if (this.showLoader) return;
                this.showLoader = true;
                this.messageIndex = 0;
                this.loadingMessage = this.messages[0];
                clearInterval(this.timer);
                this.timer = setInterval(() => {
                    this.messageIndex = (this.messageIndex + 1) % this.messages.length;
                    this.loadingMessage = this.messages[this.messageIndex];
                }, 1500);
            },

            stopLoading() {
                this.showLoader = false;
                clearInterval(this.timer);
            },

            init() {
                window.startGlobalLoader = () => this.startLoading();
                window.stopGlobalLoader = () => this.stopLoading();

                // Livewire 3 navigation lifecycle
                document.addEventListener('livewire:navigating', () => {
                    this.startLoading();
                });

                document.addEventListener('livewire:navigated', () => {
                    this.$nextTick(() => {
                        this.stopLoading();
                    });
                });

                // Instant click capture untuk link navigasi & submit form (kecuali theme toggle)
                document.addEventListener('click', (e) => {
                    if (e.target.closest('[data-theme-toggle]')) {
                        return;
                    }

                    const anchor = e.target.closest('a[href]');
                    if (anchor) {
                        const href = anchor.getAttribute('href');
                        const target = anchor.getAttribute('target');

                        if (href &&
                            !href.startsWith('#') &&
                            !href.startsWith('javascript:') &&
                            target !== '_blank' &&
                            !e.ctrlKey && !e.metaKey && !e.shiftKey) {
                            this.startLoading();
                            return;
                        }
                    }

                    const submitBtn = e.target.closest('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        this.startLoading();
                    }
                }, true);

                document.addEventListener('submit', (e) => {
                    if (!e.defaultPrevented) {
                        this.startLoading();
                    }
                });

                window.addEventListener('beforeunload', () => {
                    this.startLoading();
                });

                window.addEventListener('assets-loading-start', () => this.startLoading());
                window.addEventListener('assets-loading-stop', () => this.stopLoading());
            }
        };
    }
</script>

<style>
@keyframes progress {
    0% { transform: translateX(-100%); }
    50% { transform: translateX(20%); }
    100% { transform: translateX(100%); }
}
</style>

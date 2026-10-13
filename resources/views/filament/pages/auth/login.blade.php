<div class="fixed inset-0 z-0 flex w-screen h-screen overflow-hidden bg-gray-950 font-sans select-none">
    {{-- ========================================================================= --}}
    {{-- PANEL KIRI: VISUAL DATA SHOWCASE (55% LEBAR LAYAR)                       --}}
    {{-- ========================================================================= --}}
    <div class="relative hidden lg:flex lg:w-[55%] flex-col justify-between p-12 xl:p-16 bg-gray-950 border-r border-gray-800/80 overflow-hidden">
        {{-- Background Gradient & Radial Glow Grid --}}
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(99,102,241,0.25),rgba(255,255,255,0))] pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-gradient-to-tl from-purple-600/15 via-indigo-600/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1f293708_1px,transparent_1px),linear-gradient(to_bottom,#1f293708_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] pointer-events-none"></div>

        {{-- Top Brand Bar --}}
        <div class="relative z-10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30 border border-indigo-400/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                </div>
                <div>
                    <span class="text-xl font-black tracking-wider text-white">ASETS</span>
                    <span class="block text-[9px] font-bold tracking-widest text-indigo-400 uppercase">Enterprise Data Hub</span>
                </div>
            </div>

            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-950/60 border border-indigo-800/50 text-[11px] font-semibold text-indigo-300">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Node Cluster v1.0 • Operational</span>
            </div>
        </div>

        {{-- Center Data Showcase & Hero Statement --}}
        <div class="relative z-10 my-auto py-8 space-y-8 max-w-xl">
            {{-- Orbiting Data Particles & 3D Interactive Visualization Widget --}}
            <div class="relative flex items-center justify-center w-full h-48 py-4">
                {{-- Ambient Pulse --}}
                <div class="absolute w-40 h-40 bg-indigo-500/20 rounded-full blur-2xl animate-pulse"></div>

                {{-- Orbit SVG Rings --}}
                <div class="relative w-44 h-44 flex items-center justify-center">
                    <svg class="absolute inset-0 w-full h-full animate-[spin_10s_linear_infinite]" viewBox="0 0 160 160" fill="none">
                        <circle cx="80" cy="80" r="70" stroke="rgba(99, 102, 241, 0.3)" stroke-width="1.5" stroke-dasharray="6 6" />
                        <circle cx="80" cy="10" r="5" fill="#6366f1" />
                        <circle cx="80" cy="150" r="3.5" fill="#818cf8" />
                    </svg>

                    <svg class="absolute inset-2 w-[144px] h-[144px] animate-[spin_7s_linear_infinite_reverse]" viewBox="0 0 144 144" fill="none">
                        <circle cx="72" cy="72" r="62" stroke="rgba(168, 85, 247, 0.35)" stroke-width="1.5" stroke-dasharray="5 5" />
                        <circle cx="134" cy="72" r="4.5" fill="#a855f7" />
                        <circle cx="10" cy="72" r="3.5" fill="#c084fc" />
                    </svg>

                    {{-- Center 3D Isometric Database Stack --}}
                    <div class="relative z-10 flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 text-white shadow-2xl shadow-indigo-500/50 border border-indigo-400/40">
                        <svg class="w-10 h-10 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Hero Texts --}}
            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-xs font-bold text-indigo-400 tracking-wide uppercase">
                    DATA INTEGRATION PLATFORM
                </div>
                <h1 class="text-3xl xl:text-4xl font-extrabold text-white tracking-tight leading-tight">
                    Unified Enterprise <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-300 to-cyan-400">
                        Data Intelligence
                    </span>
                </h1>
                <p class="text-sm xl:text-base text-gray-400 leading-relaxed">
                    Platform tata kelola dan visualisasi data tabular terpusat secara real-time. Kelola skema, integrasi pipeline webhook, serta profiler kualitas data dalam satu ekosistem terpadu.
                </p>
            </div>
        </div>

        {{-- Bottom Telemetry / Quick Metrics --}}
        <div class="relative z-10 pt-6 border-t border-gray-800/80 grid grid-cols-3 gap-4 text-xs">
            <div class="space-y-0.5">
                <span class="block text-gray-500 font-medium">Integritas Data</span>
                <span class="font-bold text-gray-200 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> 100% Verified
                </span>
            </div>
            <div class="space-y-0.5">
                <span class="block text-gray-500 font-medium">Ingestion Pipeline</span>
                <span class="font-bold text-gray-200 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span> Real-time Stream
                </span>
            </div>
            <div class="space-y-0.5">
                <span class="block text-gray-500 font-medium">Security Standard</span>
                <span class="font-bold text-gray-200 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span> Encrypted Keys
                </span>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- PANEL KANAN: FORM LOGIN MODERN (45% LEBAR LAYAR)                         --}}
    {{-- ========================================================================= --}}
    <div class="relative flex flex-col justify-center items-center w-full lg:w-[45%] h-full p-6 sm:p-12 xl:p-16 bg-gray-900/90 overflow-y-auto">
        {{-- Subtle Ambient Glow Di Belakang Form --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 w-full max-w-md mx-auto space-y-8">
            {{-- Mobile Logo Banner (Tampil Hanya di Layar Kecil) --}}
            <div class="flex lg:hidden items-center justify-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                </div>
                <div class="text-left">
                    <span class="text-xl font-black tracking-wider text-white">ASETS</span>
                    <span class="block text-[9px] font-bold tracking-widest text-indigo-400 uppercase">Enterprise Data Hub</span>
                </div>
            </div>

            {{-- Form Header --}}
            <div class="text-center lg:text-left space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-[11px] font-bold uppercase tracking-wider text-indigo-400">
                    <span>Portal Autentikasi Sistem</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    Masuk ke Akun Anda
                </h2>
                <p class="text-xs sm:text-sm text-gray-400">
                    Gunakan kredensial enterprise Anda untuk mengakses konsol data intelligence.
                </p>
            </div>

            {{-- Card Container Glassmorphism --}}
            <div class="login-split-card p-8 rounded-2xl bg-gray-950/70 border border-gray-800 shadow-2xl backdrop-blur-xl">
                {{-- Render Filament Auth Content Component yang sudah berisi Form + Actions --}}
                {{ $this->content }}
            </div>

            {{-- Security & Copyright Notice --}}
            <div class="text-center space-y-1 text-xs text-gray-500">
                <p class="flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Dilindungi dengan enkripsi end-to-end TLS 1.3</span>
                </p>
                <p>© {{ date('Y') }} ASETS Enterprise Data Hub. All rights reserved.</p>
            </div>
        </div>
    </div>
</div>

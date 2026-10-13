<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Intro card --}}
        <div class="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">API Authentication & External Access Keys</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Gunakan API Key ini untuk mengakses REST API `/api/v1/datasets` dari sistem eksternal, bot ingestion, atau aplikasi analitik pihak ketiga.</p>

            <form wire:submit.prevent="generateToken" class="mt-5 flex flex-col sm:flex-row items-center gap-3">
                <input type="text"
                       wire:model="tokenName"
                       placeholder="Nama Konsumen / Kunci API (mis: Python ETL Pipeline)..."
                       class="w-full sm:max-w-md text-xs rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                <button type="submit" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold rounded-xl bg-primary-600 text-white hover:bg-primary-500 transition shadow-sm">
                    Generate New Key
                </button>
            </form>

            @if($newlyCreatedToken)
                <div class="mt-5 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800">
                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">Token Baru Berhasil Dibuat:</span>
                    <div class="mt-1 flex items-center justify-between bg-white dark:bg-gray-900 p-2.5 rounded-lg border border-emerald-100 dark:border-emerald-900">
                        <code class="text-xs font-mono text-emerald-700 dark:text-emerald-400 select-all">{{ $newlyCreatedToken }}</code>
                        <span class="text-[10px] text-gray-400">Salin & simpan token ini</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Active Tokens List --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Active Tokens List</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Daftar token aktif yang memiliki izin akses ke endpoint REST API.</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    {{ count($this->tokens) }} Tokens
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-5 font-semibold">Nama Token / Consumer Name</th>
                            <th class="py-3 px-5 font-semibold">Terakhir Digunakan</th>
                            <th class="py-3 px-5 font-semibold">Tanggal Dibuat</th>
                            <th class="py-3 px-5 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($this->tokens as $token)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                <td class="py-3.5 px-5 font-medium text-gray-900 dark:text-white flex items-center space-x-2.5">
                                    <div class="p-1.5 rounded-lg bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                    </div>
                                    <span class="font-semibold">{{ $token->name }}</span>
                                </td>
                                <td class="py-3.5 px-5 text-gray-500 dark:text-gray-400">
                                    {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Belum pernah digunakan' }}
                                </td>
                                <td class="py-3.5 px-5 font-mono text-gray-500 dark:text-gray-400">
                                    {{ $token->created_at ? $token->created_at->format('d M Y, H:i') : '-' }}
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <button type="button"
                                            wire:click="deleteToken({{ $token->id }})"
                                            wire:confirm="Yakin ingin menghapus token ini? Akses eksternal menggunakan token ini akan langsung terhenti."
                                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-400 dark:hover:bg-rose-900/60 transition shadow-sm"
                                            title="Revoke / Delete Token">
                                        <svg class="w-3.5 h-3.5 mr-1 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Revoke Token
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 px-5 text-center text-gray-400">
                                    Belum ada API token aktif. Silakan buat token baru di atas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- API Documentation Reference --}}
        <div class="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-3">
            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Endpoint Quick Reference</h4>
            <div class="space-y-2 text-xs font-mono">
                <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl flex items-center justify-between">
                    <span><strong class="text-emerald-600">GET</strong> /api/v1/datasets</span>
                    <span class="text-gray-400 font-sans">Katalog seluruh dataset</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl flex items-center justify-between">
                    <span><strong class="text-emerald-600">GET</strong> /api/v1/datasets/{id}/data?search=keyword</span>
                    <span class="text-gray-400 font-sans">Query payload record real-time</span>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl flex items-center justify-between">
                    <span><strong class="text-indigo-600">POST</strong> /api/v1/datasets/ingest</span>
                    <span class="text-gray-400 font-sans">Ingestion payload tabular otomatis</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Export Center Intro Banner --}}
        <div class="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Dataset Export & Report Dispatcher</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Unduh seluruh koleksi dataset dalam format CSV terstruktur atau JSON payload murni untuk integrasi data lake.</p>
            </div>
            <div class="flex items-center space-x-3 text-xs">
                <span class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-mono">
                    {{ number_format($totalDatasets) }} Datasets
                </span>
                <span class="px-3 py-1.5 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 font-mono font-bold">
                    {{ number_format($totalRows) }} Aggregated Rows
                </span>
            </div>
        </div>

        {{-- Dataset Export List --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800">
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Available Datasets for Immediate Export</h4>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Dataset Title</th>
                            <th class="py-3 px-4 font-semibold">Category</th>
                            <th class="py-3 px-4 font-semibold">Rows</th>
                            <th class="py-3 px-4 font-semibold">File Format</th>
                            <th class="py-3 px-4 font-semibold">Last Ingested</th>
                            <th class="py-3 px-4 font-semibold text-right">Instant Export</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($datasets as $dataset)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">
                                    {{ $dataset->title }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                        {{ $dataset->category ?? 'General' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono font-semibold">{{ number_format($dataset->record_count) }}</td>
                                <td class="py-3 px-4 font-mono uppercase">{{ $dataset->file_format ?? 'EXCEL/CSV' }}</td>
                                <td class="py-3 px-4 text-gray-400">{{ $dataset->updated_at ? $dataset->updated_at->diffForHumans() : '-' }}</td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <button type="button" wire:click="downloadDataset({{ $dataset->id }}, 'csv')" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 transition">
                                        Unduh CSV
                                    </button>
                                    <button type="button" wire:click="downloadDataset({{ $dataset->id }}, 'json')" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900 transition">
                                        Unduh JSON
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400">Belum ada dataset yang dapat diekspor.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

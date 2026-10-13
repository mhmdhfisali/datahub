<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Top Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div class="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Average Health Score</span>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $avgHealthScore }}%</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $avgHealthScore >= 80 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' }}">
                        {{ $avgHealthScore >= 80 ? 'Healthy' : 'Needs Review' }}
                    </span>
                </div>
            </div>

            <div class="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Optimal Integrity (&ge;80%)</span>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ $optimalCount }}</span>
                    <span class="text-xs text-gray-400">Datasets</span>
                </div>
            </div>

            <div class="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Moderate Integrity (50-79%)</span>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ $warningCount }}</span>
                    <span class="text-xs text-gray-400">Datasets</span>
                </div>
            </div>

            <div class="p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Critical Quality (&lt;50%)</span>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-2xl font-extrabold text-rose-600 dark:text-rose-400">{{ $criticalCount }}</span>
                    <span class="text-xs text-gray-400">Datasets</span>
                </div>
            </div>
        </div>

        {{-- Quality Audit Catalog Table --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Dataset Quality Health Catalog</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Audit menyeluruh integritas, kelengkapan kolom, dan status pemindaian dataset.</p>
                </div>
                <span class="text-xs font-mono text-gray-400">{{ $totalDatasets }} Datasets Cataloged</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Dataset Title</th>
                            <th class="py-3 px-4 font-semibold">Category</th>
                            <th class="py-3 px-4 font-semibold">Total Rows</th>
                            <th class="py-3 px-4 font-semibold">Features / Cols</th>
                            <th class="py-3 px-4 font-semibold">Health Score</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                        @forelse($datasets as $dataset)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">
                                    {{ $dataset->title }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                        {{ $dataset->category ?? 'General' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono">{{ number_format($dataset->record_count) }}</td>
                                <td class="py-3 px-4 font-mono">{{ $dataset->column_count ?? (isset($dataset->payload[0]) ? count(array_keys((array)$dataset->payload[0])) : 0) }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $dataset->health_score >= 80 ? 'bg-emerald-500' : ($dataset->health_score >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $dataset->health_score }}%"></div>
                                        </div>
                                        <span class="font-bold text-xs">{{ $dataset->health_score }}%</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $dataset->health_score >= 80 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' }}">
                                        {{ $dataset->health_score >= 80 ? 'Optimal' : 'Needs Cleansing' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ \App\Filament\Resources\Datasets\DatasetResource::getUrl('view', ['record' => $dataset]) }}" class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-900 transition">
                                        Inspect Payload &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400">Belum ada dataset yang diunggah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

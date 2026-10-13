@php
    $dataset = $record ?? (isset($getRecord) ? $getRecord() : ($this->getRecord() ?? null));
    $data = $dataset->payload ?? $dataset->payload_preview ?? [];
    $totalRecords = $dataset->record_count ?? count($data);

    // Ambil kolom dari record pertama
    $firstRow = !empty($data) ? (array) $data[0] : [];
    $rawColumns = array_keys($firstRow);

    // Filter kolom penomoran manual
    $duplicateNumberingKeys = ['no', 'nomor', 'no.', 'no_urut', 'nomor_urut', 'number', 'num'];
    $columns = array_values(array_filter($rawColumns, function($col) use ($duplicateNumberingKeys) {
        return !in_array(strtolower(trim((string)$col)), $duplicateNumberingKeys, true);
    }));

    // Hitung kelengkapan data per kolom
    $validCounts = [];
    foreach ($columns as $col) {
        $nonNull = 0;
        foreach ($data as $r) {
            $rArr = (array) $r;
            $val = $rArr[$col] ?? null;
            if ($val !== null && $val !== '' && $val !== '-' && strtolower((string)$val) !== 'null') {
                $nonNull++;
            }
        }
        $validCounts[$col] = $nonNull;
    }

    // Deteksi kolom kategorikal
    $categoricalKeywords = [
        'ukuran_baju', 'ukuran_celana', 'ukuran_sepatu', 'baju', 'celana', 'size',
        'agama', 'religion', 'jenis_kelamin', 'gender', 'jk', 'sex',
        'status', 'status_pernikahan', 'pernikahan', 'marital',
        'kota', 'kabupaten', 'provinsi', 'wilayah', 'city',
        'kategori', 'category', 'divisi', 'departemen', 'department', 'role', 'jabatan',
        'pekerjaan', 'pekerjaan_sekarang', 'pendidikan', 'goldar', 'golongan_darah',
        'rt', 'rw', 'nik'
    ];

    $categoricalColumns = [];
    foreach ($columns as $col) {
        if (($validCounts[$col] ?? 0) === 0) continue;
        $lower = strtolower((string)$col);
        foreach ($categoricalKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $categoricalColumns[] = $col;
                break;
            }
        }
    }

    // Fallback: Jika tidak terdeteksi via keyword, masukkan seluruh kolom yang memiliki data valid
    if (empty($categoricalColumns) && !empty($columns)) {
        $categoricalColumns = $columns;
    }

    // Default fitur yang dipilih pertama kali
    $defaultFeature = !empty($categoricalColumns) ? $categoricalColumns[0] : ($columns[0] ?? '');

    // Quality Score Calculation
    $missingCount = 0;
    $totalCells = count($data) * max(count($columns), 1);
    foreach ($data as $r) {
        $rArr = (array) $r;
        foreach ($columns as $c) {
            $val = $rArr[$c] ?? null;
            if ($val === null || $val === '' || $val === '-' || strtolower((string)$val) === 'null') {
                $missingCount++;
            }
        }
    }
    $completenessRatio = $totalCells > 0 ? (1 - ($missingCount / $totalCells)) : 1.0;
    $healthScore = (int) round($completenessRatio * 100);

    if ($healthScore >= 80) {
        $healthBadgeText = 'High Integrity';
        $healthBadgeClasses = 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 border border-emerald-500/30';
        $healthStrokeColor = '#10b981';
    } elseif ($healthScore >= 50) {
        $healthBadgeText = 'Moderate Health';
        $healthBadgeClasses = 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-500/30';
        $healthStrokeColor = '#f59e0b';
    } else {
        $healthBadgeText = 'Low Integrity';
        $healthBadgeClasses = 'bg-red-500/10 text-red-600 dark:bg-red-500/20 dark:text-red-400 border border-red-500/30';
        $healthStrokeColor = '#ef4444';
    }

    $circumference = 283;
    $dashOffset = (int) round($circumference - ($circumference * $healthScore / 100));
@endphp

{{-- Global Chart.js Loader --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<div class="space-y-6" x-data="datasetViewer({
    payload: {{ json_encode($data) }},
    columns: {{ json_encode($columns) }}
})">

    {{-- METRICS CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Dataset Overview</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 border border-primary-200 dark:border-primary-800 uppercase">
                        {{ $dataset->file_format ?? 'EXCEL/CSV' }}
                    </span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-4 break-words leading-snug line-clamp-2 md:line-clamp-3 hover:line-clamp-none transition-all cursor-default" title="{{ $dataset->name ?? $dataset->title }}">
                    {{ $dataset->name ?? $dataset->title }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 line-clamp-2" title="{{ $dataset->description }}">
                    {{ $dataset->description ?: 'Platform dataset intelligence preview dan profil skema data tabular real-time.' }}
                </p>
            </div>
            <div class="pt-4 border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                <div>
                    <span class="block text-gray-400">Total Baris:</span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm">{{ number_format($totalRecords) }} Baris</span>
                </div>
                <div>
                    <span class="block text-gray-400">Dimensi Kolom:</span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm">{{ count($columns) }} Kolom Fitur</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Data Quality Health Score</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $healthBadgeClasses }}">
                    {{ $healthBadgeText }}
                </span>
            </div>
            <div class="flex items-center justify-between my-2">
                <div class="space-y-1">
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ $healthScore }}%</div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $missingCount === 0 ? 'Lengkap 100% tanpa nilai kosong (null).' : number_format($missingCount) . ' sel bernilai kosong (missing).' }}
                    </p>
                </div>
                <div class="relative flex items-center justify-center flex-shrink-0" style="width: 80px; height: 80px;">
                    <svg class="transform -rotate-90" style="width: 80px; height: 80px;" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" stroke="currentColor" stroke-width="10" class="text-gray-100 dark:text-gray-800 fill-none" />
                        <circle cx="50" cy="50" r="45" stroke="{{ $healthStrokeColor }}" stroke-width="10" stroke-dasharray="283" stroke-dashoffset="{{ $dashOffset }}" stroke-linecap="round" class="fill-none transition-all duration-1000 ease-out" />
                    </svg>
                    <span class="absolute text-xs font-bold text-gray-700 dark:text-gray-300">{{ $healthScore }}%</span>
                </div>
            </div>
            <div class="text-[11px] text-gray-400 border-t border-gray-100 dark:border-gray-800 pt-3">
                Rasio kelengkapan: <strong class="text-gray-700 dark:text-gray-300">{{ number_format($completenessRatio * 100, 1) }}%</strong> sel terisi valid.
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Storage & Parsing Meta</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                    ID #{{ $dataset->id ?? '-' }}
                </span>
            </div>
            <div class="space-y-2.5 my-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Ukuran Berkas:</span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200">
                        {{ isset($dataset->file_size) && $dataset->file_size ? number_format($dataset->file_size / 1024, 1) . ' KB' : 'Disimpan Lokal' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Format Serialisasi:</span>
                    <span class="font-mono text-gray-800 dark:text-gray-200 uppercase">JSON UTF-8 Payload</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Terakhir Dimutakhirkan:</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        {{ isset($dataset->updated_at) && $dataset->updated_at ? $dataset->updated_at->diffForHumans() : 'Baru saja' }}
                    </span>
                </div>
            </div>
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                <span class="text-gray-400">Status Integritas:</span>
                <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400 font-medium">
                    <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Parsed & Synchronized
                </span>
            </div>
        </div>
    </div>

    {{-- CHART VISUALIZER COMPONENT (TERISOLASI SECARA TOTAL) --}}
    @include('filament.resources.datasets.components.dataset-chart-visualizer', [
        'dataset' => $dataset,
        'data' => $data,
        'categoricalColumns' => $categoricalColumns,
        'defaultFeature' => $defaultFeature,
    ])

    {{-- INTERACTIVE PAYLOAD TABLE INSPECTOR --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Interactive Payload Inspector</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Menampilkan seluruh data record tanpa batas halaman.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Filter semua data..."
                           class="w-full px-4 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:outline-none transition" />
                </div>
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="px-3.5 py-2 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-medium bg-white dark:bg-gray-800 hover:bg-gray-50 transition shadow-sm">
                        Ekspor Data
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg z-30 py-1.5 text-xs" style="display: none;">
                        <button type="button" @click="exportData('csv'); open = false" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">CSV (.csv)</button>
                        <button type="button" @click="exportData('json'); open = false" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">JSON (.json)</button>
                    </div>
                </div>
            </div>
        </div>

        <div style="position: relative; overflow-x: auto; overflow-y: auto; max-height: 600px;" class="m-6 border border-gray-200 dark:border-gray-800 rounded-lg">
            <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-800" style="border-collapse: separate; border-spacing: 0;">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase sticky top-0 z-20">
                    <tr>
                        <th class="py-3 px-4 font-semibold text-center w-14 sticky left-0 z-30 bg-gray-50 dark:bg-gray-800 border-b border-r border-gray-200 dark:border-gray-700">NO</th>
                        @foreach ($columns as $column)
                            <th class="py-3 px-4 font-semibold whitespace-nowrap border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                {{ strtoupper(str_replace('_', ' ', $column)) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900">
                    <template x-for="(row, idx) in getFilteredRows()" :key="idx">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                            <td class="py-2.5 px-4 text-center font-mono font-medium text-gray-400 sticky left-0 z-10 bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800" x-text="idx + 1"></td>
                            <template x-for="col in columns" :key="col">
                                <td class="py-2.5 px-4 whitespace-nowrap">
                                    <template x-if="row[col] !== null && row[col] !== undefined && row[col] !== '' && row[col] !== '-'">
                                        <span x-text="row[col]"></span>
                                    </template>
                                    <template x-if="row[col] === null || row[col] === undefined || row[col] === '' || row[col] === '-'">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono bg-gray-100 dark:bg-gray-800 text-gray-500 italic">null</span>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    function registerViewer() {
        if (typeof Alpine !== 'undefined' && !Alpine.data('datasetViewer')) {
            Alpine.data('datasetViewer', (config) => ({
                searchQuery: '',
                payloadData: config.payload || [],
                columns: config.columns || [],

                getFilteredRows() {
                    if (!this.searchQuery || this.searchQuery.trim() === '') return this.payloadData || [];
                    const q = this.searchQuery.toLowerCase().trim();
                    return (this.payloadData || []).filter(row => {
                        return this.columns.some(col => {
                            const val = row[col];
                            return val !== null && val !== undefined && String(val).toLowerCase().includes(q);
                        });
                    });
                },

                exportData(format) {
                    const rows = this.getFilteredRows();
                    if (!rows.length) return alert('Tidak ada data.');
                    if (format === 'json') {
                        const str = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(rows, null, 2));
                        const a = document.createElement('a');
                        a.href = str;
                        a.download = 'dataset_export.json';
                        a.click();
                    } else {
                        let csv = 'data:text/csv;charset=utf-8,';
                        csv += this.columns.map(c => `"${c}"`).join(',') + '\r\n';
                        rows.forEach(r => {
                            csv += this.columns.map(c => `"${(r[c] !== null && r[c] !== undefined ? String(r[c]).replace(/"/g, '""') : '')}"`).join(',') + '\r\n';
                        });
                        const a = document.createElement('a');
                        a.href = encodeURI(csv);
                        a.download = 'dataset_export.csv';
                        a.click();
                    }
                }
            }));
        }
    }

    if (window.Alpine) {
        registerViewer();
    } else {
        document.addEventListener('alpine:init', registerViewer);
    }
})();
</script>

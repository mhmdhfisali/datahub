{{-- KOMPONEN KHUSUS CHART VISUALIZER TERISOLASI TOTAL DARI LIVEWIRE HYDRATION --}}
<div wire:ignore wire:key="dataset-chart-visualizer-container-{{ $dataset->id }}"
     x-data="datasetChartVisualizer({
         payload: {{ json_encode($data) }},
         categoricalColumns: {{ json_encode($categoricalColumns) }},
         initialFeature: {{ json_encode($defaultFeature) }}
     })"
     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-5 border-b border-gray-100 dark:border-gray-800 gap-4">
        <div class="flex items-center space-x-3">
            <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/60 rounded-xl text-indigo-600 dark:text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Categorical Analytics Visualizer</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Eksplorasi distribusi frekuensi data demografis & atribut kategorikal secara real-time.</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <label for="featureSelector" class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">FITUR / ATRIBUT:</label>
            <div class="relative min-w-[220px]">
                <select id="featureSelector"
                        x-model="selectedFeature"
                        @change="renderCharts()"
                        class="w-full text-xs font-medium rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white py-2 pl-3 pr-8 focus:ring-2 focus:ring-primary-500 focus:outline-none transition">
                    <template x-for="cat in categoricalColumns" :key="cat">
                        <option :value="cat" x-text="String(cat).replace(/_/g, ' ').toUpperCase()"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>

    {{-- KONTRAINER CANVAS DENGAN WIRE:IGNORE DAN X-REF KHAS ALPINE --}}
    <div wire:ignore class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Bar Chart Box --}}
        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">DISTRIBUSI FREKUENSI (BAR CHART)</h4>
                <span class="text-xs text-indigo-500 font-mono" x-text="'Fitur: ' + String(selectedFeature).toUpperCase()"></span>
            </div>
            <div class="relative h-[280px] w-full flex items-center justify-center">
                <canvas x-ref="barCanvas"></canvas>
            </div>
        </div>

        {{-- Doughnut Chart Box --}}
        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">PROPORSI PERSENTASE (DOUGHNUT)</h4>
                <span class="text-xs text-indigo-500 font-mono">Top Categories</span>
            </div>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 h-[280px]">
                {{-- Canvas Doughnut Wrapper --}}
                <div class="relative w-full sm:w-3/5 h-[200px] sm:h-full flex items-center justify-center">
                    <canvas x-ref="doughnutCanvas"></canvas>
                </div>
                {{-- Scrollable Legend Wrapper (Mencegah teks/label terpotong) --}}
                <div class="w-full sm:w-2/5 max-h-[120px] sm:max-h-[260px] overflow-y-auto pr-1.5 space-y-1.5 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700" x-ref="doughnutLegendContainer">
                    <template x-for="(label, idx) in doughnutData.labels" :key="idx">
                        <div class="flex items-center justify-between text-[11px] gap-2 py-1 px-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/60 transition">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background-color: ' + (doughnutPalette[idx] || '#6366f1')"></span>
                                <span class="text-gray-700 dark:text-gray-300 truncate" :title="label" x-text="label"></span>
                            </div>
                            <span class="font-mono font-semibold text-gray-500 dark:text-gray-400 shrink-0" x-text="doughnutData.counts[idx]"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function registerComponent() {
        if (typeof Alpine !== 'undefined' && !Alpine.data('datasetChartVisualizer')) {
            Alpine.data('datasetChartVisualizer', (config) => ({
                payload: config.payload || [],
                categoricalColumns: config.categoricalColumns || [],
                selectedFeature: config.initialFeature || (config.categoricalColumns && config.categoricalColumns[0]) || '',
                barChartInstance: null,
                doughnutChartInstance: null,
                doughnutData: { labels: [], counts: [] },
                doughnutPalette: ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6', '#14b8a6', '#f97316', '#ef4444', '#84cc16', '#64748b'],

                init() {
                    if (!this.selectedFeature && this.categoricalColumns.length > 0) {
                        this.selectedFeature = this.categoricalColumns[0];
                    }

                    this.waitForChartJs(() => {
                        this.$nextTick(() => {
                            // Jeda 200ms menjamin Livewire hydration awal sepenuhnya selesai
                            setTimeout(() => {
                                this.renderCharts();
                            }, 200);
                        });
                    });

                    window.addEventListener('theme-changed', () => {
                        setTimeout(() => this.renderCharts(), 150);
                    });
                },

                waitForChartJs(callback) {
                    if (window.Chart) {
                        callback();
                        return;
                    }
                    let attempts = 0;
                    const interval = setInterval(() => {
                        attempts++;
                        if (window.Chart) {
                            clearInterval(interval);
                            callback();
                        } else if (attempts > 50) {
                            clearInterval(interval);
                            console.error('Chart.js gagal dimuat dari CDN.');
                        }
                    }, 100);
                },

                getFeatureData(feature) {
                    if (!this.payload || this.payload.length === 0 || !feature) {
                        return { labels: ['Tidak Ada Data'], counts: [0] };
                    }

                    const counts = {};
                    const targetKey = String(feature).trim().toLowerCase();

                    this.payload.forEach(row => {
                        const actualKey = Object.keys(row).find(k => String(k).trim().toLowerCase() === targetKey);
                        let val = actualKey ? row[actualKey] : null;

                        if (val === null || val === undefined || String(val).trim() === '' || String(val).trim() === '-' || String(val).toLowerCase() === 'null') {
                            val = '(Nilai Null / Kosong)';
                        } else {
                            val = String(val).trim();
                        }

                        counts[val] = (counts[val] || 0) + 1;
                    });

                    // Urutkan berdasarkan frekuensi terbanyak
                    const sorted = Object.entries(counts).sort((a, b) => b[1] - a[1]);

                    // Batasi ke Top 10 kategori utama dan agregasikan sisanya ke 'Lainnya'
                    let labels = [];
                    let dataCounts = [];
                    if (sorted.length > 10) {
                        const top10 = sorted.slice(0, 10);
                        const others = sorted.slice(10);
                        const othersSum = others.reduce((sum, item) => sum + item[1], 0);

                        labels = top10.map(e => e[0]);
                        dataCounts = top10.map(e => e[1]);

                        if (othersSum > 0) {
                            labels.push('Lainnya');
                            dataCounts.push(othersSum);
                        }
                    } else {
                        labels = sorted.map(e => e[0]);
                        dataCounts = sorted.map(e => e[1]);
                    }

                    return {
                        labels: labels,
                        counts: dataCounts
                    };
                },

                renderCharts() {
                    if (!window.Chart) return;

                    const barCanvas = this.$refs.barCanvas;
                    const doughnutCanvas = this.$refs.doughnutCanvas;

                    if (!barCanvas || !doughnutCanvas) return;

                    // Hancurkan instance lama secara eksplisit sebelum instansiasi baru
                    if (this.barChartInstance) {
                        this.barChartInstance.destroy();
                        this.barChartInstance = null;
                    }
                    if (this.doughnutChartInstance) {
                        this.doughnutChartInstance.destroy();
                        this.doughnutChartInstance = null;
                    }

                    const dist = this.getFeatureData(this.selectedFeature);
                    this.doughnutData = dist;

                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#f3f4f6' : '#1f2937';
                    const gridColor = isDark ? 'rgba(156, 163, 175, 0.1)' : 'rgba(229, 231, 235, 0.8)';
                    const palette = this.doughnutPalette;

                    this.barChartInstance = new Chart(barCanvas, {
                        type: 'bar',
                        data: {
                            labels: dist.labels,
                            datasets: [{
                                label: 'Frekuensi',
                                data: dist.counts,
                                backgroundColor: '#6366f1',
                                borderRadius: 6,
                                maxBarThickness: 40
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    padding: 10,
                                    cornerRadius: 8
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: textColor,
                                        callback: function(val, index) {
                                            const text = dist.labels[index] || '';
                                            return text.length > 15 ? text.substring(0, 12) + '...' : text;
                                        }
                                    },
                                    grid: { display: false }
                                },
                                y: { beginAtZero: true, ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } }
                            }
                        }
                    });

                    this.doughnutChartInstance = new Chart(doughnutCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: dist.labels,
                            datasets: [{
                                data: dist.counts,
                                backgroundColor: palette.slice(0, dist.labels.length),
                                borderColor: isDark ? '#111827' : '#ffffff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            plugins: {
                                legend: {
                                    display: false // Ditangani secara bersih dan responsif oleh custom scrollable legend container
                                },
                                tooltip: {
                                    padding: 10,
                                    cornerRadius: 8
                                }
                            },
                            cutout: '62%'
                        }
                    });
                }
            }));
        }
    }

    if (window.Alpine) {
        registerComponent();
    } else {
        document.addEventListener('alpine:init', registerComponent);
    }
})();
</script>

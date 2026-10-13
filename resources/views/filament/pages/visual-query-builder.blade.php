<x-filament-panels::page>
    {{-- Ensure Chart.js is loaded --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <div class="space-y-6"
         wire:ignore.self
         wire:key="visual-query-builder-page"
         x-data="visualQueryBuilderComponent()">

        <!-- Control Panel Container: Section Card -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between pb-4 border-b border-gray-100 dark:border-gray-800 gap-3">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 rounded-xl bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400">
                        <x-filament::icon icon="heroicon-m-chart-pie" class="w-6 h-6 max-w-[24px] max-h-[24px]" />
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-gray-900 dark:text-gray-100">Dataset Query Playground & Visualizer</h2>
                        <p class="text-xs text-gray-400">Select any ingested dataset, map payload attributes to axes, and explore charts dynamically.</p>
                    </div>
                </div>

                @if($dataset = $this->getSelectedDataset())
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 border border-primary-200 dark:border-primary-800">
                        Active: {{ $dataset->title }} ({{ number_format($dataset->record_count) }} rows)
                    </span>
                @endif
            </div>

            <!-- 3-Column Responsive Grid for Selectors -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- 1. Target Dataset -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Target Dataset</label>
                    <select wire:model.live="selectedDatasetId"
                            @change="$nextTick(() => setTimeout(() => renderChart(), 200))"
                            class="rounded-xl border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-3 w-full font-semibold">
                        @foreach($this->getDatasetOptions() as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. X-Axis Dimension -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">X-Axis (Dimension)</label>
                    <select wire:model.live="xAxisColumn"
                            @change="$nextTick(() => setTimeout(() => renderChart(), 200))"
                            class="rounded-xl border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-3 w-full">
                        @foreach($this->getAvailableColumns() as $col)
                            <option value="{{ $col }}">{{ ucwords(str_replace('_', ' ', $col)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Y-Axis Metric -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Y-Axis (Metric Column)</label>
                    <select wire:model.live="yAxisColumn"
                            @change="$nextTick(() => setTimeout(() => renderChart(), 200))"
                            class="rounded-xl border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-3 w-full">
                        @foreach($this->getAvailableColumns() as $col)
                            <option value="{{ $col }}">{{ ucwords(str_replace('_', ' ', $col)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Aggregation Function -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Aggregation Function</label>
                    <select wire:model.live="aggregation"
                            @change="$nextTick(() => setTimeout(() => renderChart(), 200))"
                            class="rounded-xl border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-3 w-full font-semibold">
                        <option value="count">COUNT (Number of occurrences)</option>
                        <option value="sum">SUM (Total calculated values)</option>
                        <option value="avg">AVG (Average mean distribution)</option>
                    </select>
                </div>

                <!-- 5. Chart Visualization Type -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Chart Visualization Type</label>
                    <select wire:model.live="chartType"
                            @change="$nextTick(() => setTimeout(() => renderChart(), 200))"
                            class="rounded-xl border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-3 w-full font-semibold">
                        <option value="bar">Bar Chart</option>
                        <option value="line">Line Chart</option>
                        <option value="doughnut">Doughnut Chart</option>
                        <option value="polarArea">Polar Area Chart</option>
                        <option value="radar">Radar Chart</option>
                    </select>
                </div>

                <!-- 6. Max Categories to Plot -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Max Groups Filter</label>
                    <select wire:model.live="maxItems"
                            @change="$nextTick(() => setTimeout(() => renderChart(), 200))"
                            class="rounded-xl border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 py-2.5 px-3 w-full">
                        <option value="5">Top 5 Elements</option>
                        <option value="10">Top 10 Elements</option>
                        <option value="15">Top 15 Elements</option>
                        <option value="25">Top 25 Elements</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Chart Canvas Container DILINDUNGI STRICT WIRE:IGNORE DENGAN X-REF -->
        <div wire:ignore
             wire:key="visualizer-canvas-container"
             class="relative w-full h-[480px] p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center space-x-2.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">
                        Live Rendered Preview
                    </span>
                </div>

                <div class="flex items-center space-x-2 text-xs text-gray-400">
                    <span>X: <strong class="text-gray-700 dark:text-gray-300">{{ ucwords(str_replace('_', ' ', (string) $xAxisColumn)) }}</strong></span>
                    <span>•</span>
                    <span>Metric: <strong class="text-gray-700 dark:text-gray-300">{{ strtoupper($aggregation) }} ({{ ucwords(str_replace('_', ' ', (string) $yAxisColumn)) }})</strong></span>
                </div>
            </div>

            <div class="relative w-full h-[360px] flex items-center justify-center">
                <canvas x-ref="visualizerCanvas"></canvas>
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[11px] text-gray-400">
                <span>Chart.js v4 + Alpine.js Reactive Engine</span>
                <span>Auto-refreshes dynamically on axis, aggregation, or dataset changes</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>

<script>
(function() {
    function registerVisualQueryBuilder() {
        if (typeof Alpine !== 'undefined' && !Alpine.data('visualQueryBuilderComponent')) {
            Alpine.data('visualQueryBuilderComponent', () => ({
                chart: null,

                init() {
                    this.waitForChartJs(() => {
                        this.$nextTick(() => {
                            setTimeout(() => this.renderChart(), 150);
                        });
                    });

                    // Tangkap event saat Livewire menyelesaikan update dataset atau sumbu
                    this.$wire.on('chart-data-updated', () => {
                        this.$nextTick(() => {
                            setTimeout(() => this.renderChart(), 50);
                        });
                    });

                    window.addEventListener('theme-changed', () => {
                        setTimeout(() => this.renderChart(), 150);
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

                renderChart() {
                    if (!window.Chart) return;

                    const ctx = this.$refs.visualizerCanvas;
                    if (!ctx) return;

                    const chartData = this.$wire.get('chartData');
                    const chartType = this.$wire.get('chartType') || 'bar';

                    if (!chartData || !chartData.labels) return;

                    if (this.chart) {
                        this.chart.destroy();
                        this.chart = null;
                    }

                    const isDark = document.documentElement.classList.contains('dark');
                    const textColor = isDark ? '#f3f4f6' : '#1f2937';
                    const labelColor = isDark ? '#9ca3af' : '#6b7280';
                    const gridColor = isDark ? 'rgba(156, 163, 175, 0.15)' : 'rgba(226, 232, 240, 0.8)';

                    this.chart = new Chart(ctx, {
                        type: chartType,
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            plugins: {
                                legend: {
                                    display: ['doughnut', 'polarArea', 'radar'].includes(chartType),
                                    labels: {
                                        color: textColor,
                                        font: { size: 12 }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: isDark ? '#1f2937' : '#ffffff',
                                    titleColor: textColor,
                                    bodyColor: textColor,
                                    borderColor: gridColor,
                                    borderWidth: 1,
                                    padding: 10,
                                    cornerRadius: 8,
                                }
                            },
                            scales: ['doughnut', 'polarArea', 'radar'].includes(chartType) ? {} : {
                                x: {
                                    grid: { color: gridColor },
                                    ticks: { color: labelColor }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: gridColor },
                                    ticks: { color: labelColor }
                                }
                            }
                        }
                    });
                }
            }));
        }
    }

    if (window.Alpine) {
        registerVisualQueryBuilder();
    } else {
        document.addEventListener('alpine:init', registerVisualQueryBuilder);
    }
})();
</script>

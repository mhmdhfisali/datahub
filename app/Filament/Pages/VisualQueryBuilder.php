<?php

namespace App\Filament\Pages;

use App\Models\Dataset;
use BackedEnum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use UnitEnum;

class VisualQueryBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'ANALYTICS & INSIGHTS';

    protected static ?string $navigationLabel = 'Data Visualizer & Charts';

    protected static ?string $title = 'Interactive Visual Query Builder';

    protected static ?string $slug = 'data-visualizer';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.visual-query-builder';

    public ?int $selectedDatasetId = null;

    public ?string $xAxisColumn = null;

    public ?string $yAxisColumn = null;

    public string $chartType = 'bar'; // bar, line, doughnut, radar, polarArea

    public string $aggregation = 'count'; // count, sum, avg

    public int $maxItems = 10;

    public function mount(): void
    {
        $first = Dataset::whereNotNull('payload')->latest()->first();
        if ($first) {
            $this->selectedDatasetId = $first->id;
            $this->autoDetectAxes();
        }
    }

    public function updatedSelectedDatasetId(): void
    {
        $this->autoDetectAxes();
        $this->dispatch('chart-data-updated');
    }

    public function updatedChartType(): void
    {
        $this->dispatch('chart-data-updated');
    }

    public function updatedXAxisColumn(): void
    {
        $this->dispatch('chart-data-updated');
    }

    public function updatedYAxisColumn(): void
    {
        $this->dispatch('chart-data-updated');
    }

    public function updatedAggregation(): void
    {
        $this->dispatch('chart-data-updated');
    }

    public function updatedMaxItems(): void
    {
        $this->dispatch('chart-data-updated');
    }

    public function getSelectedDataset(): ?Dataset
    {
        return $this->selectedDatasetId ? Dataset::find($this->selectedDatasetId) : null;
    }

    public function getDatasetOptions(): array
    {
        return Dataset::pluck('title', 'id')->toArray();
    }

    public function getAvailableColumns(): array
    {
        $dataset = $this->getSelectedDataset();
        if (! $dataset || empty($dataset->payload)) {
            return [];
        }

        $row = (array) $dataset->payload[0];

        return array_keys($row);
    }

    protected function autoDetectAxes(): void
    {
        $dataset = $this->getSelectedDataset();
        if (! $dataset || empty($dataset->payload)) {
            return;
        }

        $row = (array) $dataset->payload[0];
        $keys = array_keys($row);

        // Cari sumbu kategorikal (nama, kota, kategori, status)
        $categoricalCandidates = ['category', 'city', 'status', 'department', 'role', 'jenis_kelamin', 'kota', 'nama', 'title'];
        $detectedX = null;
        foreach ($keys as $k) {
            foreach ($categoricalCandidates as $cand) {
                if (str_contains(strtolower($k), $cand)) {
                    $detectedX = $k;
                    break 2;
                }
            }
        }
        $this->xAxisColumn = $detectedX ?? $keys[0] ?? null;

        // Cari sumbu numerik (age, salary, count, total, score, nilai)
        $numericCandidates = ['salary', 'amount', 'total', 'age', 'score', 'price', 'quantity', 'gaji', 'nilai'];
        $detectedY = null;
        foreach ($keys as $k) {
            foreach ($numericCandidates as $cand) {
                if (str_contains(strtolower($k), $cand)) {
                    $detectedY = $k;
                    break 2;
                }
            }
        }
        $this->yAxisColumn = $detectedY ?? $keys[1] ?? $this->xAxisColumn;
    }

    /**
     * Compute aggregated chart payload for Chart.js rendering
     */
    public function getChartDataProperty(): array
    {
        $dataset = $this->getSelectedDataset();
        if (! $dataset || empty($dataset->payload) || empty($this->xAxisColumn)) {
            return [
                'labels' => ['No Data'],
                'datasets' => [
                    [
                        'label' => 'Empty',
                        'data' => [0],
                        'backgroundColor' => ['#94a3b8'],
                    ],
                ],
            ];
        }

        $payload = $dataset->payload;
        $xCol = $this->xAxisColumn;
        $yCol = $this->yAxisColumn;
        $agg = $this->aggregation;

        $groups = [];

        foreach ($payload as $item) {
            $item = (array) $item;
            $xVal = (string) ($item[$xCol] ?? 'N/A');
            $yVal = is_numeric($item[$yCol] ?? null) ? (float) $item[$yCol] : 1;

            if (! isset($groups[$xVal])) {
                $groups[$xVal] = [
                    'count' => 0,
                    'sum' => 0,
                ];
            }
            $groups[$xVal]['count']++;
            $groups[$xVal]['sum'] += $yVal;
        }

        $results = [];
        foreach ($groups as $label => $metric) {
            $val = match ($agg) {
                'sum' => $metric['sum'],
                'avg' => $metric['count'] > 0 ? round($metric['sum'] / $metric['count'], 2) : 0,
                default => $metric['count'], // count
            };
            $results[$label] = $val;
        }

        arsort($results);
        $results = array_slice($results, 0, $this->maxItems, true);

        $labels = array_keys($results);
        $data = array_values($results);

        $colors = [
            '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316',
            '#eab308', '#10b981', '#06b6d4', '#0ea5e9', '#3b82f6',
        ];

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => strtoupper($agg).' ('.ucwords(str_replace('_', ' ', (string) $yCol)).')',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor' => '#6366f1',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
        ];
    }
}

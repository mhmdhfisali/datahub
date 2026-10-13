<?php

namespace App\Filament\Widgets;

use App\Models\Dataset;
use Filament\Widgets\ChartWidget;

class IngestionTrendChart extends ChartWidget
{
    protected ?string $heading = 'Data Ingestion Timeline / Activity Stream';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '280px';

    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'Last 7 Days',
            'month' => 'Last 30 Days',
            'year' => 'This Year',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        $days = match ($activeFilter) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 7,
        };

        $startDate = now()->subDays($days - 1)->startOfDay();
        $datasets = Dataset::where('created_at', '>=', $startDate)->get();

        $labels = [];
        $counts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $label = $days > 30 ? $date->format('M Y') : $date->format('d M');

            if ($days > 30 && in_array($label, $labels)) {
                continue;
            }

            $labels[] = $label;

            $dayDatasets = $datasets->filter(function ($d) use ($dateKey, $days) {
                if ($days > 30) {
                    return $d->created_at?->format('Y-m') === substr($dateKey, 0, 7);
                }

                return $d->created_at?->format('Y-m-d') === $dateKey;
            });

            $counts[] = $dayDatasets->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingested Datasets',
                    'data' => $counts,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

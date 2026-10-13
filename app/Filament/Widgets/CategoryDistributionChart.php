<?php

namespace App\Filament\Widgets;

use App\Models\Dataset;
use Filament\Widgets\ChartWidget;

class CategoryDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Datasets Distribution by Category';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $distribution = Dataset::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(8)
            ->pluck('count', 'category')
            ->toArray();

        $labels = array_keys($distribution);
        $values = array_values($distribution);

        if (empty($labels)) {
            $labels = ['Uncategorized'];
            $values = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Datasets Count',
                    'data' => $values,
                    'backgroundColor' => [
                        '#6366f1',
                        '#10b981',
                        '#8b5cf6',
                        '#f59e0b',
                        '#06b6d4',
                        '#ec4899',
                        '#3b82f6',
                        '#84cc16',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'display' => true,
                ],
            ],
        ];
    }
}

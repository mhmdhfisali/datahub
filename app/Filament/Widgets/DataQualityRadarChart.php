<?php

namespace App\Filament\Widgets;

use App\Models\Dataset;
use Filament\Widgets\ChartWidget;

class DataQualityRadarChart extends ChartWidget
{
    protected ?string $heading = 'Data Health & Quality Index Matrix';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $datasets = Dataset::all();

        $completenessScore = 100;
        $uniquenessScore = 100;
        $typeConsistencyScore = 100;
        $structureScore = 100;
        $freshnessScore = 100;

        if ($datasets->isNotEmpty()) {
            $scores = [];
            $uniqueness = [];
            $completeness = [];
            $consistency = [];

            foreach ($datasets as $d) {
                $report = $d->health_report ?? [];
                $scores[] = $d->health_score;

                $missingPct = $report['missing_values_percentage'] ?? 0;
                $completeness[] = max(0, 100 - $missingPct);

                $dups = $report['duplicate_rows_count'] ?? 0;
                $rows = max(1, $d->record_count);
                $uniqueness[] = max(0, 100 - (($dups / $rows) * 100));

                $warnings = count($report['warnings'] ?? []);
                $consistency[] = max(0, 100 - ($warnings * 15));
            }

            $completenessScore = (int) round(array_sum($completeness) / count($completeness));
            $uniquenessScore = (int) round(array_sum($uniqueness) / count($uniqueness));
            $typeConsistencyScore = (int) round(array_sum($consistency) / count($consistency));
            $structureScore = (int) round(array_sum($scores) / count($scores));

            $latest = $datasets->sortByDesc('updated_at')->first();
            if ($latest && $latest->updated_at->diffInDays(now()) > 30) {
                $freshnessScore = 70;
            } else {
                $freshnessScore = 95;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Health Matrix %',
                    'data' => [
                        $completenessScore,
                        $uniquenessScore,
                        $typeConsistencyScore,
                        $structureScore,
                        $freshnessScore,
                    ],
                    'backgroundColor' => 'rgba(245, 158, 11, 0.25)',
                    'borderColor' => '#f59e0b',
                    'pointBackgroundColor' => '#f59e0b',
                    'pointBorderColor' => '#ffffff',
                    'pointHoverBackgroundColor' => '#ffffff',
                    'pointHoverBorderColor' => '#f59e0b',
                ],
            ],
            'labels' => [
                'Completeness',
                'Uniqueness',
                'Consistency',
                'Validity',
                'Freshness',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'radar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'r' => [
                    'min' => 0,
                    'max' => 100,
                    'ticks' => [
                        'stepSize' => 20,
                    ],
                ],
            ],
        ];
    }
}

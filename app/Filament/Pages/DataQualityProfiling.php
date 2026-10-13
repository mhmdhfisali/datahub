<?php

namespace App\Filament\Pages;

use App\Models\Dataset;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class DataQualityProfiling extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|UnitEnum|null $navigationGroup = 'CORE PLATFORM';

    protected static ?string $navigationLabel = 'Data Quality & Profiling';

    protected static ?string $title = 'Data Quality & Schema Profiling Catalog';

    protected static ?string $slug = 'data-quality';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.data-quality-profiling';

    public function getViewData(): array
    {
        $datasets = Dataset::latest()->get();

        $totalDatasets = $datasets->count();
        $avgHealthScore = (int) round($datasets->avg('health_score') ?? 100);
        $totalRows = (int) $datasets->sum('record_count');
        $optimalCount = $datasets->where('health_score', '>=', 80)->count();
        $warningCount = $datasets->whereBetween('health_score', [50, 79])->count();
        $criticalCount = $datasets->where('health_score', '<', 50)->count();

        return [
            'datasets' => $datasets,
            'totalDatasets' => $totalDatasets,
            'avgHealthScore' => $avgHealthScore,
            'totalRows' => $totalRows,
            'optimalCount' => $optimalCount,
            'warningCount' => $warningCount,
            'criticalCount' => $criticalCount,
        ];
    }
}

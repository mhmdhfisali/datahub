<?php

namespace App\Filament\Widgets;

use App\Models\Dataset;
use App\Models\WebhookSubscription;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalDatasets = Dataset::count();
        $thisMonthDatasets = Dataset::where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonthDatasets = Dataset::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth(),
        ])->count();

        $growth = $lastMonthDatasets > 0
            ? round((($thisMonthDatasets - $lastMonthDatasets) / $lastMonthDatasets) * 100)
            : ($thisMonthDatasets > 0 ? 100 : 0);

        $totalAggregatedRows = (int) Dataset::sum('record_count');
        $avgHealthScore = (int) round(Dataset::avg('health_score') ?? 100);

        $activeWebhooks = WebhookSubscription::where('is_active', true)->count();
        $apiConsumers = DB::table('personal_access_tokens')->count();
        $totalIntegrations = $activeWebhooks + $apiConsumers;

        return [
            Stat::make('Total Ingested Datasets', number_format($totalDatasets))
                ->description(($growth >= 0 ? "+{$growth}%" : "{$growth}%").' from last month')
                ->descriptionIcon($growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growth >= 0 ? 'success' : 'danger')
                ->chart([2, 4, 3, 6, 5, 8, max($totalDatasets, 1)]),

            Stat::make('Total Aggregated Rows', number_format($totalAggregatedRows))
                ->description('Jumlah seluruh baris payload')
                ->descriptionIcon('heroicon-m-table-cells')
                ->color('primary')
                ->chart([10, 30, 60, 120, 250, 400, max($totalAggregatedRows, 1)]),

            Stat::make('Average Quality Health Score', "{$avgHealthScore}%")
                ->description($avgHealthScore >= 80 ? 'Optimal Health Index' : ($avgHealthScore >= 50 ? 'Moderate Health' : 'Needs Cleansing'))
                ->descriptionIcon($avgHealthScore >= 80 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-triangle')
                ->color($avgHealthScore >= 80 ? 'success' : ($avgHealthScore >= 50 ? 'warning' : 'danger'))
                ->chart([85, 90, 88, 92, 94, max($avgHealthScore, 1)]),

            Stat::make('Active Webhooks & API Consumers', number_format($totalIntegrations))
                ->description("{$activeWebhooks} Active Webhooks • {$apiConsumers} Tokens")
                ->descriptionIcon('heroicon-m-bolt')
                ->color('info')
                ->chart([1, 1, 2, 2, 3, max($totalIntegrations, 1)]),
        ];
    }
}

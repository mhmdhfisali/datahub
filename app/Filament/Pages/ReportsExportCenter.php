<?php

namespace App\Filament\Pages;

use App\Models\Dataset;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ReportsExportCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static string|UnitEnum|null $navigationGroup = 'ANALYTICS & INSIGHTS';

    protected static ?string $navigationLabel = 'Reports & Export Center';

    protected static ?string $title = 'Reports Generation & Dataset Export Center';

    protected static ?string $slug = 'reports-export';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.reports-export-center';

    public function getViewData(): array
    {
        return [
            'datasets' => Dataset::latest()->get(),
            'totalDatasets' => Dataset::count(),
            'totalRows' => (int) Dataset::sum('record_count'),
        ];
    }

    public function downloadDataset(int $id, string $format = 'csv'): StreamedResponse
    {
        $dataset = Dataset::findOrFail($id);
        $items = $dataset->payload ?? [];
        $titleSlug = Str::slug($dataset->title ?: 'dataset_'.$dataset->id);

        if ($format === 'json') {
            $filename = "{$titleSlug}.json";

            return response()->streamDownload(function () use ($items) {
                echo json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }, $filename, ['Content-Type' => 'application/json']);
        }

        $filename = "{$titleSlug}.csv";

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            if (! empty($items) && is_array($items)) {
                $first = (array) ($items[0] ?? []);
                fputcsv($handle, array_keys($first));
                foreach ($items as $row) {
                    fputcsv($handle, (array) $row);
                }
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

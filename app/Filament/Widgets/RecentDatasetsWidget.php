<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Datasets\DatasetResource;
use App\Models\Dataset;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecentDatasetsWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Ingestion & Quality Audit Table';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Dataset::query()->latest()->limit(5))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Dataset Title')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('record_count')
                    ->label('Total Rows')
                    ->numeric()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('health_score')
                    ->label('Data Quality Score')
                    ->badge()
                    ->color(fn (Dataset $record): string => $record->health_color)
                    ->formatStateUsing(fn ($state, Dataset $record) => "{$state}% • {$record->health_label}"),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ingested At')
                    ->dateTime('d M Y, H:i')
                    ->since(),
            ])
            ->recordActions([
                Action::make('view_payload')
                    ->label('View Payload')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Dataset $record): string => DatasetResource::getUrl('view', ['record' => $record])),

                Action::make('download_csv')
                    ->label('CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (Dataset $record) => $this->downloadCsv($record)),
            ]);
    }

    public function downloadCsv(Dataset $dataset): StreamedResponse
    {
        $items = $dataset->payload ?? [];
        $filename = Str::slug($dataset->title).'.csv';

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            if (! empty($items) && is_array($items) && isset($items[0]) && is_array($items[0])) {
                fputcsv($handle, array_keys($items[0]));
                foreach ($items as $row) {
                    if (is_array($row)) {
                        fputcsv($handle, $row);
                    }
                }
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

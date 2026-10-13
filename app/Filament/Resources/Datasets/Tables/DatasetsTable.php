<?php

namespace App\Filament\Resources\Datasets\Tables;

use App\Models\Dataset;
use App\Services\ActivityLogger;
use App\Services\DataHealthProfiler;
use App\Services\WebhookDispatcherService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class DatasetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Dataset Title')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Dataset $record) => $record->file_type ? strtoupper($record->file_type).' file • v'.$record->current_version : 'v'.$record->current_version),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('record_count')
                    ->label('Rows')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('column_count')
                    ->label('Cols')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('health_score')
                    ->label('Health Index')
                    ->badge()
                    ->color(fn (Dataset $record): string => $record->health_color)
                    ->formatStateUsing(fn ($state, Dataset $record) => "{$state}% {$record->health_label}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_ingested_at')
                    ->label('Last Ingested')
                    ->dateTime('d M Y, H:i')
                    ->since()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('d M Y, H:i')
                    ->since()
                    ->sortable()
                    ->visible(fn () => request()->get('activeTab') === 'trash' || request()->has('tableFilters'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(fn () => Dataset::distinct('category')->pluck('category', 'category')->toArray()),

                Tables\Filters\SelectFilter::make('file_type')
                    ->label('Source Format')
                    ->options([
                        'xlsx' => 'Excel (.xlsx)',
                        'xls' => 'Excel 97 (.xls)',
                        'csv' => 'CSV (.csv)',
                        'json' => 'JSON Payload',
                    ]),

                Tables\Filters\Filter::make('health_score')
                    ->form([
                        Select::make('rating')
                            ->label('Quality Rating')
                            ->options([
                                'high' => 'High Quality (80-100%)',
                                'moderate' => 'Moderate (50-79%)',
                                'needs_cleaning' => 'Needs Cleaning (<50%)',
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['rating'] ?? null) {
                            'high' => $query->where('health_score', '>=', 80),
                            'moderate' => $query->whereBetween('health_score', [50, 79]),
                            'needs_cleaning' => $query->where('health_score', '<', 50),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('record_count_threshold')
                    ->form([
                        TextInput::make('min_records')
                            ->numeric()
                            ->label('Minimum Records Threshold'),
                    ])
                    ->query(function ($query, array $data) {
                        if (! empty($data['min_records'])) {
                            return $query->where('record_count', '>=', (int) $data['min_records']);
                        }

                        return $query;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function (Dataset $record) {
                        ActivityLogger::log('deleted', "Moved dataset to trash: {$record->title}", $record);
                        app(WebhookDispatcherService::class)->dispatch('dataset.deleted', $record);
                    }),
                RestoreAction::make()
                    ->after(function (Dataset $record) {
                        ActivityLogger::log('restored', "Restored dataset from trash: {$record->title}", $record);
                        app(WebhookDispatcherService::class)->dispatch('dataset.restored', $record);
                    }),
                ForceDeleteAction::make()
                    ->after(function (Dataset $record) {
                        ActivityLogger::log('force_deleted', "Permanently deleted dataset: {$record->title}", $record);
                        app(WebhookDispatcherService::class)->dispatch('dataset.force_deleted', $record);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_assign_category')
                        ->label('Assign Category')
                        ->icon('heroicon-o-tag')
                        ->form([
                            TextInput::make('new_category')
                                ->label('New Category Name')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $category = trim($data['new_category']);
                            $records->each(fn (Dataset $d) => $d->update(['category' => $category]));

                            ActivityLogger::log('bulk_category', "Bulk assigned category '{$category}' to {$records->count()} datasets");

                            Notification::make()
                                ->title("Updated {$records->count()} datasets")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('bulk_health_scan')
                        ->label('Run Health Scan')
                        ->icon('heroicon-o-heart')
                        ->action(function (Collection $records): void {
                            $profiler = app(DataHealthProfiler::class);
                            foreach ($records as $dataset) {
                                $profile = $profiler->profile($dataset->payload ?? []);
                                $dataset->update([
                                    'health_score' => $profile['score'] ?? 100,
                                    'health_report' => $profile,
                                    'record_count' => count($dataset->payload ?? []),
                                    'column_count' => count(array_keys($dataset->payload[0] ?? [])),
                                ]);
                            }

                            ActivityLogger::log('bulk_health_scan', "Ran data quality health scan on {$records->count()} datasets");

                            Notification::make()
                                ->title("Scanned {$records->count()} datasets")
                                ->body('Quality scores and health reports have been recalculated.')
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('bulk_export_catalog')
                        ->label('Export Selected Catalog')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            return response()->streamDownload(function () use ($records) {
                                $handle = fopen('php://output', 'w');
                                fputcsv($handle, ['ID', 'Title', 'Category', 'Record Count', 'Columns', 'Health Score', 'File Type', 'Last Ingested']);
                                foreach ($records as $d) {
                                    fputcsv($handle, [
                                        $d->id,
                                        $d->title,
                                        $d->category,
                                        $d->record_count,
                                        $d->column_count,
                                        $d->health_score,
                                        $d->file_type,
                                        $d->last_ingested_at?->toDateTimeString() ?? '-',
                                    ]);
                                }
                                fclose($handle);
                            }, 'bulk_datasets_export_'.now()->format('Ymd_His').'.csv', [
                                'Content-Type' => 'text/csv',
                            ]);
                        }),

                    DeleteBulkAction::make()
                        ->after(fn (Collection $records) => ActivityLogger::log('bulk_deleted', "Moved {$records->count()} datasets to trash")),
                    RestoreBulkAction::make()
                        ->after(fn (Collection $records) => ActivityLogger::log('bulk_restored', "Restored {$records->count()} datasets from trash")),
                    ForceDeleteBulkAction::make()
                        ->after(fn (Collection $records) => ActivityLogger::log('bulk_force_deleted', "Permanently deleted {$records->count()} datasets")),
                ]),
            ]);
    }
}

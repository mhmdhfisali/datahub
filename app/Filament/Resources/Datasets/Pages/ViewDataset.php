<?php

namespace App\Filament\Resources\Datasets\Pages;

use App\Filament\Resources\Datasets\DatasetResource;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\ActivityLogger;
use App\Services\AiCopilotService;
use App\Services\DataHealthProfiler;
use App\Services\DataTransformerService;
use App\Services\PiiMaskingService;
use App\Services\WebhookDispatcherService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewDataset extends ViewRecord
{
    protected static string $resource = DatasetResource::class;

    protected string $view = 'filament.resources.dataset-resource.pages.view-dataset';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Visualisasi & Dashboard Data')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('payload')
                            ->label('')
                            ->view('filament.resources.datasets.view-payload'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        /** @var Dataset $record */
        $record = $this->getRecord();

        return [
            Actions\ActionGroup::make([
                Actions\Action::make('remove_duplicates')
                    ->label('Remove Duplicate Rows')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn () => $this->applyRemoveDuplicates()),

                Actions\Action::make('trim_whitespaces')
                    ->label('Trim Whitespaces')
                    ->icon('heroicon-o-scissors')
                    ->action(fn () => $this->applyTrimWhitespaces()),

                Actions\Action::make('fill_missing')
                    ->label('Fill Missing Values')
                    ->icon('heroicon-o-sparkles')
                    ->form([
                        TextInput::make('fallback_value')
                            ->label('Fallback Fill Value')
                            ->default('N/A')
                            ->required(),
                    ])
                    ->action(fn (array $data) => $this->applyFillMissing($data['fallback_value'])),

                Actions\Action::make('find_and_replace')
                    ->label('Find & Replace Key/Value')
                    ->icon('heroicon-o-arrows-right-left')
                    ->form([
                        TextInput::make('search')
                            ->label('Search Text')
                            ->required(),
                        TextInput::make('replace')
                            ->label('Replace With')
                            ->required(),
                    ])
                    ->action(fn (array $data) => $this->applyFindAndReplace($data['search'], $data['replace'])),

                Actions\Action::make('case_normalization')
                    ->label('Normalize Text Case')
                    ->icon('heroicon-o-language')
                    ->form([
                        Select::make('case_type')
                            ->label('Target Case')
                            ->options([
                                'title' => 'Title Case (Capitalize Each Word)',
                                'upper' => 'UPPERCASE',
                                'lower' => 'lowercase',
                            ])
                            ->default('title')
                            ->required(),
                    ])
                    ->action(fn (array $data) => $this->applyCaseNormalization($data['case_type'])),

                Actions\Action::make('mask_pii_now')
                    ->label('Mask PII Sensitive Data')
                    ->icon('heroicon-o-shield-check')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn () => $this->applyPiiMasking()),

                Actions\Action::make('run_ai_analysis')
                    ->label('Generate AI Executive Summary')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->action(fn () => $this->applyAiSummary()),
            ])
                ->label('Data Transformer & Cleaner')
                ->icon('heroicon-m-wrench-screwdriver')
                ->color('warning'),

            Actions\Action::make('version_history')
                ->label('v'.$record->current_version.' History')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->modalHeading('Snapshot Version History & Rollback')
                ->modalWidth('4xl')
                ->modalContent(fn () => view('filament.resources.datasets.version-history-modal', [
                    'dataset' => $record,
                    'versions' => $record->versions()->get(),
                ])),

            Actions\ActionGroup::make([
                Actions\Action::make('export_csv')
                    ->label('Export as CSV')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn () => $this->exportToCsv()),

                Actions\Action::make('export_json')
                    ->label('Export as JSON')
                    ->icon('heroicon-o-code-bracket')
                    ->action(fn () => $this->exportToJson()),
            ])
                ->label('Export Dataset')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success'),

            Actions\EditAction::make(),
        ];
    }

    public function applyRemoveDuplicates(): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $transformer = app(DataTransformerService::class);
        $result = $transformer->removeDuplicates($dataset->payload ?? []);

        if (($result['removed_count'] ?? 0) === 0) {
            Notification::make()
                ->title('No duplicates found')
                ->info()
                ->send();

            return;
        }

        $this->updateDatasetPayload(
            $dataset,
            $result['rows'] ?? [],
            "Removed {$result['removed_count']} duplicate rows."
        );

        Notification::make()
            ->title('Duplicates Removed')
            ->body("Successfully purged {$result['removed_count']} redundant rows.")
            ->success()
            ->send();
    }

    public function applyTrimWhitespaces(): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $transformer = app(DataTransformerService::class);
        $result = $transformer->trimWhitespace($dataset->payload ?? []);

        $this->updateDatasetPayload(
            $dataset,
            $result['rows'] ?? [],
            'Trimmed whitespaces across '.($result['trimmed_count'] ?? 0).' values.'
        );

        Notification::make()
            ->title('Whitespaces Trimmed')
            ->body('Cleaned '.($result['trimmed_count'] ?? 0).' field values.')
            ->success()
            ->send();
    }

    public function applyFillMissing(string $fallback): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $transformer = app(DataTransformerService::class);
        $result = $transformer->fillMissingValues($dataset->payload ?? [], $fallback);

        $this->updateDatasetPayload(
            $dataset,
            $result['rows'] ?? [],
            'Filled '.($result['filled_count'] ?? 0)." missing values with '{$fallback}'."
        );

        Notification::make()
            ->title('Missing Values Filled')
            ->body('Substituted '.($result['filled_count'] ?? 0).' blank cells.')
            ->success()
            ->send();
    }

    public function applyFindAndReplace(string $search, string $replace): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $transformer = app(DataTransformerService::class);
        $result = $transformer->findAndReplace($dataset->payload ?? [], $search, $replace);

        $this->updateDatasetPayload(
            $dataset,
            $result['rows'] ?? [],
            "Find & replace '{$search}' with '{$replace}' (".($result['replaced_count'] ?? 0).' changes).'
        );

        Notification::make()
            ->title('Payload Transformed')
            ->body('Updated '.($result['replaced_count'] ?? 0).' occurrences.')
            ->success()
            ->send();
    }

    public function applyCaseNormalization(string $caseType): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $transformer = app(DataTransformerService::class);
        $result = $transformer->normalizeCase($dataset->payload ?? [], $caseType);

        $this->updateDatasetPayload(
            $dataset,
            $result['rows'] ?? [],
            "Normalized casing to {$caseType} across ".($result['transformed_count'] ?? 0).' entries.'
        );

        Notification::make()
            ->title('Case Normalization Applied')
            ->body('Transformed '.($result['transformed_count'] ?? 0).' string values.')
            ->success()
            ->send();
    }

    public function applyPiiMasking(): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $piiMasker = app(PiiMaskingService::class);
        $result = $piiMasker->mask($dataset->payload ?? []);

        $this->updateDatasetPayload(
            $dataset,
            $result['rows'] ?? [],
            'Anonymized '.($result['masked_count'] ?? 0).' PII sensitive values across columns: '.implode(', ', $result['columns_masked'] ?? [])
        );

        Notification::make()
            ->title('PII Data Masked')
            ->body('Secured '.($result['masked_count'] ?? 0).' sensitive attributes.')
            ->success()
            ->send();
    }

    public function applyAiSummary(): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $aiCopilot = app(AiCopilotService::class);
        $result = $aiCopilot->generateExecutiveSummary($dataset);

        $dataset->update([
            'ai_summary' => $result['summary'] ?? null,
            'ai_suggested_tags' => $result['tags'] ?? null,
        ]);

        Notification::make()
            ->title('AI Executive Summary Generated')
            ->body('Data intelligence briefing has been updated.')
            ->success()
            ->send();
    }

    public function rollbackVersion(int $versionNumber): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $version = $dataset->versions()->where('version_number', $versionNumber)->firstOrFail();

        $this->updateDatasetPayload(
            $dataset,
            $version->payload ?? [],
            "Rolled back dataset payload to Version {$versionNumber}"
        );

        Notification::make()
            ->title("Restored to Version {$versionNumber}")
            ->success()
            ->send();
    }

    protected function updateDatasetPayload(Dataset $dataset, array $newPayload, string $summary): void
    {
        $profiler = app(DataHealthProfiler::class);
        $profile = $profiler->profile($newPayload);

        $newVersion = ($dataset->current_version ?? 1) + 1;

        $dataset->update([
            'payload' => $newPayload,
            'record_count' => count($newPayload),
            'column_count' => count(array_keys($newPayload[0] ?? [])),
            'health_score' => $profile['score'] ?? 100,
            'health_report' => $profile,
            'current_version' => $newVersion,
            'last_ingested_at' => now(),
        ]);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'user_id' => Auth::id(),
            'version_number' => $newVersion,
            'payload' => $newPayload,
            'record_count' => count($newPayload),
            'health_score' => $profile['score'] ?? 100,
            'change_summary' => $summary,
        ]);

        ActivityLogger::log('cleaned', $summary, $dataset);
        app(WebhookDispatcherService::class)->dispatch('dataset.cleaned', $dataset, [
            'change_summary' => $summary,
            'version' => $newVersion,
        ]);
    }

    public function exportToCsv(): StreamedResponse
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $items = $dataset->payload ?? [];
        $filename = Str::slug($dataset->title).'.csv';

        ActivityLogger::log('export_csv', "Exported dataset CSV: {$dataset->title}", $dataset);

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

    public function exportToJson(): StreamedResponse
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();
        $filename = Str::slug($dataset->title).'.json';

        ActivityLogger::log('export_json', "Exported dataset JSON: {$dataset->title}", $dataset);

        return response()->streamDownload(function () use ($dataset) {
            echo json_encode($dataset->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
}

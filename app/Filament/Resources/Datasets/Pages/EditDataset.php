<?php

namespace App\Filament\Resources\Datasets\Pages;

use App\Filament\Resources\Datasets\DatasetResource;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\ActivityLogger;
use App\Services\DataHealthProfiler;
use App\Services\DatasetParserService;
use App\Services\WebhookDispatcherService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditDataset extends EditRecord
{
    protected static string $resource = DatasetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Dataset $record */
        $record = $this->getRecord();

        $fileChanged = ! empty($data['excel_file']) && $data['excel_file'] !== $record->excel_file;
        $payloadChanged = isset($data['payload']) && $data['payload'] !== $record->payload;

        if ($fileChanged || $payloadChanged) {
            $parser = app(DatasetParserService::class);
            $profiler = app(DataHealthProfiler::class);

            $parsed = $parser->parse(
                $data['excel_file'] ?? null,
                $data['payload'] ?? null
            );

            $payload = $parsed['payload'] ?? [];
            $profile = $profiler->profile($payload);

            $data['payload'] = $payload;
            $data['record_count'] = $parsed['record_count'] ?? count($payload);
            $data['column_count'] = $parsed['column_count'] ?? count(array_keys($payload[0] ?? []));
            if (! empty($parsed['file_type'])) {
                $data['file_type'] = $parsed['file_type'];
            }
            if (! empty($parsed['file_size_bytes'])) {
                $data['file_size_bytes'] = $parsed['file_size_bytes'];
            }
            $data['health_score'] = $profile['score'] ?? 100;
            $data['health_report'] = $profile;
            $data['last_ingested_at'] = now();
            $data['current_version'] = ($record->current_version ?? 1) + 1;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();

        DatasetVersion::firstOrCreate([
            'dataset_id' => $dataset->id,
            'version_number' => $dataset->current_version,
        ], [
            'user_id' => Auth::id(),
            'payload' => $dataset->payload ?? [],
            'record_count' => $dataset->record_count,
            'health_score' => $dataset->health_score,
            'change_summary' => 'Updated dataset attributes and payload via Edit Form',
        ]);

        ActivityLogger::log('updated', "Updated dataset: {$dataset->title} (v{$dataset->current_version})", $dataset);
        app(WebhookDispatcherService::class)->dispatch('dataset.updated', $dataset);
    }
}

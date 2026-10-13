<?php

namespace App\Filament\Resources\Datasets\Pages;

use App\Filament\Resources\Datasets\DatasetResource;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\ActivityLogger;
use App\Services\AiCopilotService;
use App\Services\DataHealthProfiler;
use App\Services\DatasetParserService;
use App\Services\PiiMaskingService;
use App\Services\WebhookDispatcherService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDataset extends CreateRecord
{
    protected static string $resource = DatasetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $maskPii = ! empty($data['mask_pii']);
        $parser = app(DatasetParserService::class);
        $profiler = app(DataHealthProfiler::class);
        $piiMasker = app(PiiMaskingService::class);

        $parsed = $parser->parse(
            $data['excel_file'] ?? null,
            $data['payload'] ?? null
        );

        if (! empty($parsed['error'])) {
            Notification::make()
                ->title('Warning during data ingestion')
                ->body($parsed['error'])
                ->warning()
                ->send();
        }

        $payload = $parsed['payload'] ?? [];

        if ($maskPii && ! empty($payload)) {
            $maskResult = $piiMasker->mask($payload);
            $payload = $maskResult['rows'] ?? $payload;
        }

        $profile = $profiler->profile($payload);

        $data['user_id'] = Auth::id();
        $data['payload'] = $payload;
        $data['record_count'] = $parsed['record_count'] ?? count($payload);
        $data['column_count'] = $parsed['column_count'] ?? count(array_keys($payload[0] ?? []));
        $data['file_type'] = $parsed['file_type'] ?? 'manual';
        $data['file_size_bytes'] = $parsed['file_size_bytes'] ?? 0;
        $data['health_score'] = $profile['score'] ?? 100;
        $data['health_report'] = $profile;
        $data['last_ingested_at'] = now();
        $data['ingestion_status'] = ($profile['score'] ?? 100) >= 50 ? 'success' : 'warning';
        $data['ingestion_progress'] = 100;
        $data['current_version'] = 1;

        unset($data['mask_pii']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Dataset $dataset */
        $dataset = $this->getRecord();

        $aiCopilot = app(AiCopilotService::class);
        $aiData = $aiCopilot->generateExecutiveSummary($dataset);
        $dataset->update([
            'ai_summary' => $aiData['summary'] ?? null,
            'ai_suggested_tags' => $aiData['tags'] ?? null,
        ]);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'user_id' => Auth::id(),
            'version_number' => 1,
            'payload' => $dataset->payload ?? [],
            'record_count' => $dataset->record_count,
            'health_score' => $dataset->health_score,
            'change_summary' => 'Initial ingestion via '.($dataset->file_type ?? 'manual entry'),
        ]);

        ActivityLogger::log('created', "Created dataset: {$dataset->title} ({$dataset->record_count} records)", $dataset);
        app(WebhookDispatcherService::class)->dispatch('dataset.created', $dataset);
    }
}

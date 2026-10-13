<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Services\ActivityLogger;
use App\Services\AiCopilotService;
use App\Services\DataHealthProfiler;
use App\Services\DatasetParserService;
use App\Services\PiiMaskingService;
use App\Services\WebhookDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessLargeDatasetIngestion implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $datasetId,
        public ?string $filePath = null,
        public ?string $manualPayload = null,
        public bool $maskPii = false,
        public ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        DatasetParserService $parser,
        DataHealthProfiler $profiler,
        PiiMaskingService $piiMasker,
        AiCopilotService $aiCopilot,
        WebhookDispatcherService $webhooks
    ): void {
        $dataset = Dataset::find($this->datasetId);
        if (! $dataset) {
            return;
        }

        try {
            $dataset->update([
                'ingestion_status' => 'processing',
                'ingestion_progress' => 15,
            ]);

            $parsed = $parser->parse($this->filePath, $this->manualPayload);
            $rows = $parsed['payload'];

            $dataset->update(['ingestion_progress' => 45]);

            if ($this->maskPii) {
                $maskResult = $piiMasker->mask($rows);
                $rows = $maskResult['rows'];
            }

            $dataset->update(['ingestion_progress' => 70]);

            $profile = $profiler->profile($rows);
            $dataset->payload = $rows;
            $dataset->record_count = count($rows);
            $dataset->column_count = $parsed['column_count'];
            $dataset->file_type = $parsed['file_type'] ?? $dataset->file_type;
            $dataset->file_size_bytes = $parsed['file_size_bytes'] ?? $dataset->file_size_bytes;
            $dataset->health_score = $profile['score'];
            $dataset->health_report = $profile;
            $dataset->ingestion_progress = 85;
            $dataset->save();

            // Run AI briefing
            $aiData = $aiCopilot->generateExecutiveSummary($dataset);
            $dataset->ai_summary = $aiData['summary'] ?? null;
            $dataset->ai_suggested_tags = $aiData['tags'] ?? null;
            $dataset->ingestion_status = $profile['score'] >= 50 ? 'success' : 'warning';
            $dataset->ingestion_progress = 100;
            $dataset->last_ingested_at = now();
            $dataset->save();

            // Version snapshot
            DatasetVersion::create([
                'dataset_id' => $dataset->id,
                'user_id' => $this->userId,
                'version_number' => $dataset->current_version,
                'payload' => $rows,
                'record_count' => count($rows),
                'health_score' => $profile['score'],
                'change_summary' => 'Queued background parsing completed ('.count($rows).' rows)',
            ]);

            ActivityLogger::log('queued_ingestion_completed', "Async parsing finished: {$dataset->title}", $dataset);
            $webhooks->dispatch('dataset.created', $dataset);
        } catch (\Throwable $e) {
            Log::error("Async dataset ingestion failed for ID {$this->datasetId}: {$e->getMessage()}");
            $dataset->update([
                'ingestion_status' => 'failed',
                'ingestion_progress' => 0,
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\Dataset;
use App\Models\DatasetVersion;
use Illuminate\Support\Facades\Auth;

class DatasetService
{
    public function __construct(
        protected DatasetParserService $parser,
        protected DataHealthProfiler $profiler,
        protected PiiMaskingService $piiMasker,
        protected AiCopilotService $aiCopilot,
        protected WebhookDispatcherService $webhookDispatcher
    ) {}

    /**
     * Ingest and prepare dataset form data before creation.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function ingestFormData(array $data, ?int $userId = null): array
    {
        $maskPii = ! empty($data['mask_pii']);

        $parsed = $this->parser->parse(
            $data['excel_file'] ?? null,
            $data['payload'] ?? null
        );

        $payload = is_array($parsed['payload'] ?? null) ? $parsed['payload'] : [];

        // Apply PII Masking if requested
        if ($maskPii && ! empty($payload)) {
            $maskResult = $this->piiMasker->mask($payload);
            $payload = $maskResult['rows'];
        }

        $profile = $this->profiler->profile($payload);

        $data['user_id'] = $userId ?? Auth::id();
        $data['payload'] = $payload;
        $data['record_count'] = (int) ($parsed['record_count'] ?? count($payload));
        $data['column_count'] = (int) ($parsed['column_count'] ?? (! empty($payload[0]) && is_array($payload[0]) ? count(array_keys($payload[0])) : 0));
        $data['file_type'] = $parsed['file_type'] ?? null;
        $data['file_size_bytes'] = $parsed['file_size_bytes'] ?? null;
        $data['health_score'] = (int) ($profile['score'] ?? 100);
        $data['health_report'] = $profile;
        $data['last_ingested_at'] = now();
        $data['ingestion_status'] = ($profile['score'] ?? 100) >= 50 ? 'success' : 'warning';
        $data['ingestion_progress'] = 100;
        $data['current_version'] = 1;

        if (! empty($parsed['error'])) {
            $data['_ingestion_warning'] = $parsed['error'];
        }

        unset($data['mask_pii']);

        return $data;
    }

    /**
     * Post-creation workflow (AI executive briefing, Version 1 snapshot, Activity log, Webhooks).
     */
    public function handleCreated(Dataset $dataset, ?int $userId = null): void
    {
        $authorId = $userId ?? Auth::id();

        // 1. Generate AI Executive Summary & Tags
        $aiData = $this->aiCopilot->generateExecutiveSummary($dataset);
        $dataset->update([
            'ai_summary' => $aiData['summary'] ?? null,
            'ai_suggested_tags' => $aiData['tags'] ?? null,
        ]);

        // 2. Save Version 1 snapshot
        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'user_id' => $authorId,
            'version_number' => 1,
            'payload' => is_array($dataset->payload) ? $dataset->payload : [],
            'record_count' => (int) ($dataset->record_count ?? 0),
            'health_score' => (int) ($dataset->health_score ?? 100),
            'change_summary' => 'Initial ingestion via '.($dataset->file_type ?? 'manual entry'),
        ]);

        // 3. Audit Log
        ActivityLogger::log('created', "Created dataset: {$dataset->title} ({$dataset->record_count} records)", $dataset);

        // 4. Webhook Dispatch
        $this->webhookDispatcher->dispatch('dataset.created', $dataset);
    }

    /**
     * Mutate form data before updating an existing dataset.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateFormData(Dataset $dataset, array $data): array
    {
        $fileChanged = ! empty($data['excel_file']) && $data['excel_file'] !== $dataset->excel_file;
        $payloadChanged = isset($data['payload']) && $data['payload'] !== $dataset->payload;

        if ($fileChanged || $payloadChanged) {
            $parsed = $this->parser->parse(
                $data['excel_file'] ?? null,
                $data['payload'] ?? null
            );

            $payload = is_array($parsed['payload'] ?? null) ? $parsed['payload'] : [];
            $profile = $this->profiler->profile($payload);

            $data['payload'] = $payload;
            $data['record_count'] = (int) ($parsed['record_count'] ?? count($payload));
            $data['column_count'] = (int) ($parsed['column_count'] ?? (! empty($payload[0]) && is_array($payload[0]) ? count(array_keys($payload[0])) : 0));

            if (! empty($parsed['file_type'])) {
                $data['file_type'] = $parsed['file_type'];
            }
            if (! empty($parsed['file_size_bytes'])) {
                $data['file_size_bytes'] = $parsed['file_size_bytes'];
            }

            $data['health_score'] = (int) ($profile['score'] ?? 100);
            $data['health_report'] = $profile;
            $data['last_ingested_at'] = now();
            $data['current_version'] = ($dataset->current_version ?? 1) + 1;
        }

        return $data;
    }

    /**
     * Post-update workflow (Version snapshot, Activity log, Webhooks).
     */
    public function handleUpdated(Dataset $dataset, ?int $userId = null): void
    {
        $authorId = $userId ?? Auth::id();

        DatasetVersion::firstOrCreate([
            'dataset_id' => $dataset->id,
            'version_number' => $dataset->current_version ?? 1,
        ], [
            'user_id' => $authorId,
            'payload' => is_array($dataset->payload) ? $dataset->payload : [],
            'record_count' => (int) ($dataset->record_count ?? 0),
            'health_score' => (int) ($dataset->health_score ?? 100),
            'change_summary' => 'Updated dataset attributes and payload via Edit Form',
        ]);

        ActivityLogger::log('updated', "Updated dataset: {$dataset->title} (v{$dataset->current_version})", $dataset);
        $this->webhookDispatcher->dispatch('dataset.updated', $dataset);
    }

    /**
     * Clean, transform, or update payload with automated version bump, profiling, and auditing.
     *
     * @param  array<int, array<string, mixed>>  $newPayload
     */
    public function updatePayload(Dataset $dataset, array $newPayload, string $summary, ?int $userId = null): Dataset
    {
        $authorId = $userId ?? Auth::id();
        $profile = $this->profiler->profile($newPayload);
        $newVersion = ($dataset->current_version ?? 1) + 1;
        $columnCount = ! empty($newPayload[0]) && is_array($newPayload[0]) ? count(array_keys($newPayload[0])) : 0;

        $dataset->update([
            'payload' => $newPayload,
            'record_count' => count($newPayload),
            'column_count' => $columnCount,
            'health_score' => (int) ($profile['score'] ?? 100),
            'health_report' => $profile,
            'current_version' => $newVersion,
            'last_ingested_at' => now(),
        ]);

        DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'user_id' => $authorId,
            'version_number' => $newVersion,
            'payload' => $newPayload,
            'record_count' => count($newPayload),
            'health_score' => (int) ($profile['score'] ?? 100),
            'change_summary' => $summary,
        ]);

        ActivityLogger::log('cleaned', $summary, $dataset);

        $this->webhookDispatcher->dispatch('dataset.cleaned', $dataset, [
            'change_summary' => $summary,
            'version' => $newVersion,
        ]);

        return $dataset->fresh();
    }

    /**
     * Rollback dataset payload to a specific version number.
     */
    public function rollbackVersion(Dataset $dataset, int $versionNumber, ?int $userId = null): Dataset
    {
        $version = $dataset->versions()->where('version_number', $versionNumber)->firstOrFail();
        $payload = is_array($version->payload) ? $version->payload : [];

        return $this->updatePayload($dataset, $payload, "Rolled back dataset payload to Version {$versionNumber}", $userId);
    }

    /**
     * Trigger AI executive summary and tag generation.
     *
     * @return array{summary: string, tags: array<string>}
     */
    public function generateAiSummary(Dataset $dataset): array
    {
        $result = $this->aiCopilot->generateExecutiveSummary($dataset);

        $dataset->update([
            'ai_summary' => $result['summary'] ?? null,
            'ai_suggested_tags' => $result['tags'] ?? null,
        ]);

        return $result;
    }
}

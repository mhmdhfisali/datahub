<?php

namespace App\Services;

use App\Models\Dataset;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookDispatcherService
{
    /**
     * Dispatch event to all active matching webhook subscriptions.
     *
     * @param  string  $event  e.g. "dataset.created", "dataset.updated", "dataset.cleaned"
     * @param  array<string, mixed>  $extraPayload
     */
    public function dispatch(string $event, Dataset $dataset, array $extraPayload = []): void
    {
        $subscriptions = WebhookSubscription::where('is_active', true)->get();

        $payloadData = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'dataset' => [
                'id' => $dataset->id,
                'title' => $dataset->title,
                'category' => $dataset->category,
                'record_count' => $dataset->record_count,
                'column_count' => $dataset->column_count,
                'health_score' => $dataset->health_score,
                'current_version' => $dataset->current_version,
                'last_ingested_at' => $dataset->last_ingested_at?->toIso8601String(),
            ],
            'meta' => $extraPayload,
        ];

        $jsonPayload = json_encode($payloadData);

        foreach ($subscriptions as $sub) {
            $events = is_array($sub->events) ? $sub->events : [];
            if (! in_array('*', $events) && ! in_array($event, $events)) {
                continue;
            }

            try {
                $headers = [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'DataHub-Webhook-Dispatcher/1.0',
                    'X-DataHub-Event' => $event,
                ];

                if (! empty($sub->secret)) {
                    $headers['X-DataHub-Signature'] = hash_hmac('sha256', $jsonPayload ?: '', $sub->secret);
                }

                Http::timeout(5)
                    ->withHeaders($headers)
                    ->post($sub->url, $payloadData);

                $sub->update(['last_triggered_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning("Webhook delivery failed for URL {$sub->url}: {$e->getMessage()}");
            }
        }
    }
}

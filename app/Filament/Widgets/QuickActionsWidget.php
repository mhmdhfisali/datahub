<?php

namespace App\Filament\Widgets;

use App\Models\Dataset;
use App\Services\ActivityLogger;
use App\Services\AiCopilotService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions-widget';

    public ?string $generatedToken = null;

    public function generateApiKey(): void
    {
        $user = Auth::user();
        if (! $user) {
            Notification::make()
                ->title('Authentication required')
                ->danger()
                ->send();

            return;
        }

        $token = $user->createToken('DataHub-Dashboard-'.now()->timestamp);
        $this->generatedToken = $token->plainTextToken;

        ActivityLogger::log('api_token_generated', 'Generated new API key from Dashboard');

        Notification::make()
            ->title('API Token Created')
            ->body('Copy token now: '.$this->generatedToken)
            ->success()
            ->persistent()
            ->send();
    }

    public function cleanTempFiles(): void
    {
        $dirs = [
            storage_path('app/private/livewire-tmp'),
            storage_path('app/livewire-tmp'),
        ];

        $cleanedFiles = 0;
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = File::files($dir);
                foreach ($files as $f) {
                    File::delete($f->getPathname());
                    $cleanedFiles++;
                }
            }
        }

        ActivityLogger::log('temp_clean', "Cleaned {$cleanedFiles} temporary upload files.");

        Notification::make()
            ->title('Temporary Files Cleaned')
            ->body("Removed {$cleanedFiles} cached upload files.")
            ->success()
            ->send();
    }

    public function exportSummaryReport(): StreamedResponse
    {
        $datasets = Dataset::all();

        ActivityLogger::log('export_summary', 'Exported dataset summary catalog');

        return response()->streamDownload(function () use ($datasets) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Title', 'Category', 'Record Count', 'Columns', 'Health Score', 'File Type', 'Created At']);
            foreach ($datasets as $d) {
                fputcsv($handle, [
                    $d->id,
                    $d->title,
                    $d->category,
                    $d->record_count,
                    $d->column_count,
                    $d->health_score,
                    $d->file_type,
                    $d->created_at->toDateTimeString(),
                ]);
            }
            fclose($handle);
        }, 'datahub_summary_report_'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function runAiInsightsAll(): void
    {
        $datasets = Dataset::all();
        $aiCopilot = app(AiCopilotService::class);
        $updated = 0;

        foreach ($datasets as $dataset) {
            $brief = $aiCopilot->generateExecutiveSummary($dataset);
            $dataset->update([
                'ai_summary' => $brief['summary'],
                'ai_suggested_tags' => $brief['tags'],
            ]);
            $updated++;
        }

        ActivityLogger::log('ai_insights_batch', "Executed AI intelligence briefings for {$updated} datasets");

        Notification::make()
            ->title('AI Insights Complete')
            ->body("Executive briefings generated for {$updated} datasets.")
            ->success()
            ->send();
    }
}

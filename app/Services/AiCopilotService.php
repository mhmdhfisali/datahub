<?php

namespace App\Services;

use App\Models\Dataset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCopilotService
{
    /**
     * Auto AI Data Summarizer: Generates a plain-English executive summary and actionable insights.
     */
    public function generateExecutiveSummary(Dataset $dataset): array
    {
        $payload = $dataset->payload ?? [];
        $totalRows = count($payload);
        $cols = ! empty($payload[0]) ? array_keys($payload[0]) : [];
        $sample = array_slice($payload, 0, 5);

        $apiKey = env('GEMINI_API_KEY') ?: env('OPENAI_API_KEY');

        // If external AI API Key is provided, query LLM
        if (! empty($apiKey)) {
            try {
                if (env('GEMINI_API_KEY')) {
                    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key='.env('GEMINI_API_KEY');
                    $prompt = "Analyze this dataset summary and provide a concise, executive briefing (2 paragraphs) with data quality insights:\n"
                        ."Dataset Title: {$dataset->title}\n"
                        ."Category: {$dataset->category}\n"
                        ."Total Rows: {$totalRows}\n"
                        .'Columns: '.implode(', ', $cols)."\n"
                        .'Sample Rows: '.json_encode($sample);

                    $res = Http::timeout(10)->post($url, [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]],
                        ],
                    ]);

                    if ($res->successful()) {
                        $text = $res->json('candidates.0.content.parts.0.text');
                        if (! empty($text)) {
                            return [
                                'summary' => trim($text),
                                'tags' => $this->inferSchemaTags($dataset),
                                'source' => 'Gemini AI',
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("AI Copilot API call failed, falling back to local heuristic analysis: {$e->getMessage()}");
            }
        }

        // High-Intelligence Local Heuristic Engine Fallback
        return $this->generateHeuristicSummary($dataset);
    }

    /**
     * Local analytical briefing generator
     */
    protected function generateHeuristicSummary(Dataset $dataset): array
    {
        $payload = $dataset->payload ?? [];
        $totalRows = count($payload);
        $cols = ! empty($payload[0]) ? array_keys($payload[0]) : [];
        $health = $dataset->health_score ?? 100;
        $report = $dataset->health_report ?? [];

        $topStats = [];
        foreach ($cols as $col) {
            if (in_array(strtolower($col), ['ukuran_baju', 'jenis_kelamin', 'status', 'kota', 'provinsi'])) {
                $counts = [];
                foreach ($payload as $r) {
                    if (isset($r[$col]) && $r[$col] !== '-') {
                        $val = (string) $r[$col];
                        $counts[$val] = ($counts[$val] ?? 0) + 1;
                    }
                }
                if (! empty($counts)) {
                    arsort($counts);
                    $topVal = array_key_first($counts);
                    $topStats[] = ucwords(str_replace('_', ' ', $col))." predominantly features '{$topVal}' (".$counts[$topVal].' occurrences)';
                }
            }
        }

        $highlightText = ! empty($topStats) ? implode('; ', $topStats).'.' : 'Data distribution appears well-balanced across recorded attributes.';

        $summary = "Executive Briefing: '{$dataset->title}' comprises {$totalRows} structured records spanning ".count($cols)." detected attributes in domain '{$dataset->category}'. "
            ."Data health index stands at {$health}% with {$dataset->health_label} status (".($report['missing_values_percentage'] ?? 0).'% null rate, '.($report['duplicate_rows_count'] ?? 0).' duplicates). '
            ."Key Observations: {$highlightText} System recommends automated whitespace trimming and scheduled validation checks.";

        return [
            'summary' => $summary,
            'tags' => $this->inferSchemaTags($dataset),
            'source' => 'DataHub Intelligence Engine',
        ];
    }

    /**
     * AI Schema & Type Inferrer: Suggest tags and categorical schema
     */
    public function inferSchemaTags(Dataset $dataset): array
    {
        $cols = ! empty($dataset->payload[0]) ? array_keys($dataset->payload[0]) : [];
        $tags = [$dataset->category];

        foreach ($cols as $c) {
            $lower = strtolower($c);
            if (str_contains($lower, 'nik') || str_contains($lower, 'ktp')) {
                $tags[] = 'Citizenship Data';
            }
            if (str_contains($lower, 'email') || str_contains($lower, 'phone') || str_contains($lower, 'hp')) {
                $tags[] = 'Contact Directory';
            }
            if (str_contains($lower, 'alamat') || str_contains($lower, 'kota') || str_contains($lower, 'provinsi')) {
                $tags[] = 'Geospatial';
            }
            if (str_contains($lower, 'ukuran_baju') || str_contains($lower, 'ukuran_celana')) {
                $tags[] = 'Logistics / Apparel';
            }
            if (str_contains($lower, 'organisasi') || str_contains($lower, 'lembaga')) {
                $tags[] = 'Community / Civic';
            }
        }

        return array_values(array_unique($tags));
    }

    /**
     * Natural Language Query & Filter Engine:
     * Parses queries like "status is active", "category Laskar", "rows where age > 25", or freeform queries
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function filterByNaturalLanguage(array $rows, string $query): array
    {
        $q = trim(strtolower($query));
        if (empty($q)) {
            return $rows;
        }

        // Extract key terms
        // Pattern: "where [column] is|contains|equals|= [value]"
        if (preg_match('/(?:where|filter)\s+([a-z0-9_]+)\s*(?:is|=|contains|equals|:)\s*([a-z0-9_\-\s]+)/i', $q, $matches)) {
            $colMatch = trim(strtolower($matches[1]));
            $valMatch = trim(strtolower($matches[2]));

            return array_values(array_filter($rows, function ($row) use ($colMatch, $valMatch) {
                if (! is_array($row)) {
                    return false;
                }
                foreach ($row as $k => $v) {
                    if (str_contains(strtolower($k), $colMatch)) {
                        return str_contains(strtolower((string) $v), $valMatch);
                    }
                }

                return false;
            }));
        }

        // Freeform token matching
        $tokens = array_filter(explode(' ', $q), fn ($t) => strlen($t) > 2 && ! in_array($t, ['the', 'and', 'with', 'for', 'show', 'all', 'where', 'rows']));

        if (empty($tokens)) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) use ($tokens) {
            if (! is_array($row)) {
                return false;
            }
            $rowString = strtolower(json_encode($row));
            foreach ($tokens as $token) {
                if (str_contains($rowString, $token)) {
                    return true;
                }
            }

            return false;
        }));
    }
}

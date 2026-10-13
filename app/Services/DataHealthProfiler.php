<?php

namespace App\Services;

class DataHealthProfiler
{
    /**
     * Profile a given payload and calculate quality metrics and health score (0-100).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     score: int,
     *     total_rows: int,
     *     total_columns: int,
     *     duplicate_rows_count: int,
     *     missing_values_percentage: float,
     *     column_stats: array<string, array{
     *         missing_count: int,
     *         missing_pct: float,
     *         inconsistent_types: bool,
     *         types_found: array<string>
     *     }>,
     *     warnings: array<string>
     * }
     */
    public function profile(array $rows): array
    {
        $totalRows = count($rows);
        if ($totalRows === 0) {
            return [
                'score' => 100,
                'total_rows' => 0,
                'total_columns' => 0,
                'duplicate_rows_count' => 0,
                'missing_values_percentage' => 0.0,
                'column_stats' => [],
                'warnings' => ['Payload is empty.'],
            ];
        }

        // Get all unique columns across all rows
        $allColumns = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                foreach (array_keys($row) as $key) {
                    $allColumns[$key] = true;
                }
            }
        }
        $columns = array_keys($allColumns);
        $totalCols = count($columns);

        if ($totalCols === 0) {
            return [
                'score' => 50,
                'total_rows' => $totalRows,
                'total_columns' => 0,
                'duplicate_rows_count' => 0,
                'missing_values_percentage' => 0.0,
                'column_stats' => [],
                'warnings' => ['No columns detected in rows.'],
            ];
        }

        // 1. Detect Duplicate Rows
        $rowSignatures = [];
        $duplicateRowsCount = 0;
        foreach ($rows as $row) {
            $normalizedRow = is_array($row) ? $row : [];
            // Remove row index/no column if present for duplicate evaluation
            $copy = $normalizedRow;
            unset($copy['no'], $copy['#'], $copy['id']);
            $sig = md5(json_encode($copy));
            if (isset($rowSignatures[$sig])) {
                $duplicateRowsCount++;
            } else {
                $rowSignatures[$sig] = true;
            }
        }

        // 2. Missing Values and Type Consistency per Column
        $columnStats = [];
        $totalCells = $totalRows * $totalCols;
        $totalMissingCells = 0;
        $warnings = [];

        foreach ($columns as $col) {
            $missingInCol = 0;
            $typesFound = [];

            foreach ($rows as $row) {
                $val = $row[$col] ?? null;

                if (self::isMissingValue($val)) {
                    $missingInCol++;
                    $totalMissingCells++;
                } else {
                    $type = self::detectDataType($val);
                    $typesFound[$type] = ($typesFound[$type] ?? 0) + 1;
                }
            }

            $missingPct = $totalRows > 0 ? round(($missingInCol / $totalRows) * 100, 1) : 0;

            // Inconsistent types: when a column mixes numeric and string or boolean
            $hasInconsistentTypes = false;
            if (count($typesFound) > 1) {
                // If contains both numeric and alphabetic text
                if (isset($typesFound['numeric']) && isset($typesFound['string'])) {
                    $hasInconsistentTypes = true;
                    $warnings[] = "Column '{$col}' has mixed data types (numbers and text).";
                }
            }

            if ($missingPct >= 50) {
                $warnings[] = "Column '{$col}' is missing in {$missingPct}% of rows.";
            }

            $columnStats[$col] = [
                'missing_count' => $missingInCol,
                'missing_pct' => $missingPct,
                'inconsistent_types' => $hasInconsistentTypes,
                'types_found' => array_keys($typesFound),
            ];
        }

        if ($duplicateRowsCount > 0) {
            $warnings[] = "Detected {$duplicateRowsCount} duplicate record(s).";
        }

        // 3. Compute Health Score (0 - 100)
        // Deduct based on missing cells % (up to 45 pts deduction)
        $missingPctOverall = $totalCells > 0 ? ($totalMissingCells / $totalCells) * 100 : 0;
        $missingDeduction = min(45, ($missingPctOverall * 0.7));

        // Deduct based on duplicate rows % (up to 30 pts deduction)
        $duplicatePct = $totalRows > 0 ? ($duplicateRowsCount / $totalRows) * 100 : 0;
        $duplicateDeduction = min(30, ($duplicatePct * 0.6));

        // Deduct based on type inconsistencies (up to 25 pts deduction)
        $inconsistentCols = count(array_filter($columnStats, fn ($s) => $s['inconsistent_types']));
        $inconsistentDeduction = min(25, $inconsistentCols * 8);

        $score = (int) round(max(0, 100 - $missingDeduction - $duplicateDeduction - $inconsistentDeduction));

        return [
            'score' => $score,
            'total_rows' => $totalRows,
            'total_columns' => $totalCols,
            'duplicate_rows_count' => $duplicateRowsCount,
            'missing_values_percentage' => round($missingPctOverall, 1),
            'column_stats' => $columnStats,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    public static function isMissingValue(mixed $val): bool
    {
        if (is_null($val)) {
            return true;
        }

        if (is_string($val)) {
            $trimmed = trim($val);

            return $trimmed === '' || $trimmed === '-' || strtolower($trimmed) === 'null' || strtolower($trimmed) === 'n/a';
        }

        return false;
    }

    public static function detectDataType(mixed $val): string
    {
        if (is_numeric($val)) {
            return 'numeric';
        }

        if (is_bool($val) || $val === 'true' || $val === 'false') {
            return 'boolean';
        }

        return 'string';
    }
}

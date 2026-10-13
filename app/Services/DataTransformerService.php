<?php

namespace App\Services;

class DataTransformerService
{
    /**
     * Remove duplicate rows from payload
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, removed_count: int}
     */
    public function removeDuplicates(array $rows): array
    {
        $unique = [];
        $seen = [];
        $removed = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $copy = $row;
            unset($copy['no'], $copy['#'], $copy['id']);
            $sig = md5(json_encode($copy));

            if (isset($seen[$sig])) {
                $removed++;
            } else {
                $seen[$sig] = true;
                $unique[] = $row;
            }
        }

        return [
            'rows' => array_values($unique),
            'removed_count' => $removed,
        ];
    }

    /**
     * Trim whitespaces from all text entries in payload
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, trimmed_count: int}
     */
    public function trimWhitespace(array $rows): array
    {
        $trimmedCount = 0;
        $updatedRows = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $updatedRows[] = $row;

                continue;
            }

            $newRow = [];
            foreach ($row as $key => $val) {
                if (is_string($val)) {
                    $trimmed = trim($val);
                    if ($trimmed !== $val) {
                        $trimmedCount++;
                    }
                    $newRow[$key] = $trimmed;
                } else {
                    $newRow[$key] = $val;
                }
            }
            $updatedRows[] = $newRow;
        }

        return [
            'rows' => $updatedRows,
            'trimmed_count' => $trimmedCount,
        ];
    }

    /**
     * Fill missing values in payload with default fallback or strip nulls
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string|null  $targetColumn  If null, applies to all columns
     * @return array{rows: array<int, array<string, mixed>>, filled_count: int}
     */
    public function fillMissingValues(array $rows, string $fallbackValue = 'N/A', ?string $targetColumn = null): array
    {
        $filledCount = 0;
        $updatedRows = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $updatedRows[] = $row;

                continue;
            }

            $newRow = [];
            foreach ($row as $key => $val) {
                if ($targetColumn !== null && $key !== $targetColumn) {
                    $newRow[$key] = $val;

                    continue;
                }

                if (DataHealthProfiler::isMissingValue($val)) {
                    $newRow[$key] = $fallbackValue;
                    $filledCount++;
                } else {
                    $newRow[$key] = $val;
                }
            }
            $updatedRows[] = $newRow;
        }

        return [
            'rows' => $updatedRows,
            'filled_count' => $filledCount,
        ];
    }

    /**
     * Find and replace key or value across entire payload
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string|null  $specificColumn  If provided, only affects that column
     * @return array{rows: array<int, array<string, mixed>>, replaced_count: int}
     */
    public function findAndReplace(array $rows, string $search, string $replace, ?string $specificColumn = null): array
    {
        $replacedCount = 0;
        $updatedRows = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $updatedRows[] = $row;

                continue;
            }

            $newRow = [];
            foreach ($row as $key => $val) {
                if ($specificColumn !== null && $key !== $specificColumn) {
                    $newRow[$key] = $val;

                    continue;
                }

                if (is_string($val) && str_contains($val, $search)) {
                    $newRow[$key] = str_replace($search, $replace, $val);
                    $replacedCount++;
                } else {
                    $newRow[$key] = $val;
                }
            }
            $updatedRows[] = $newRow;
        }

        return [
            'rows' => $updatedRows,
            'replaced_count' => $replacedCount,
        ];
    }

    /**
     * Case Normalization (UPPERCASE, lowercase, Title Case)
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string  $caseType  'upper', 'lower', or 'title'
     * @param  string|null  $targetColumn  If null, normalizes all string fields
     * @return array{rows: array<int, array<string, mixed>>, transformed_count: int}
     */
    public function normalizeCase(array $rows, string $caseType = 'title', ?string $targetColumn = null): array
    {
        $transformedCount = 0;
        $updatedRows = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $updatedRows[] = $row;

                continue;
            }

            $newRow = [];
            foreach ($row as $key => $val) {
                if ($targetColumn !== null && $key !== $targetColumn) {
                    $newRow[$key] = $val;

                    continue;
                }

                if (is_string($val) && $val !== '-' && ! empty($val)) {
                    $normalized = match ($caseType) {
                        'upper' => mb_strtoupper($val),
                        'lower' => mb_strtolower($val),
                        'title' => mb_convert_case(mb_strtolower($val), MB_CASE_TITLE, 'UTF-8'),
                        default => $val,
                    };

                    if ($normalized !== $val) {
                        $transformedCount++;
                    }
                    $newRow[$key] = $normalized;
                } else {
                    $newRow[$key] = $val;
                }
            }
            $updatedRows[] = $newRow;
        }

        return [
            'rows' => $updatedRows,
            'transformed_count' => $transformedCount,
        ];
    }
}

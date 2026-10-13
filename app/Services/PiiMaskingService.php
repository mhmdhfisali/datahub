<?php

namespace App\Services;

class PiiMaskingService
{
    /**
     * Anonymize sensitive fields (email, phone numbers, NIK, credit cards)
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string>|null  $specificColumns  If null, auto-detects by column name & value pattern
     * @return array{rows: array<int, array<string, mixed>>, masked_count: int, columns_masked: array<string>}
     */
    public function mask(array $rows, ?array $specificColumns = null): array
    {
        $maskedCount = 0;
        $columnsMasked = [];
        $updatedRows = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $updatedRows[] = $row;

                continue;
            }

            $newRow = [];
            foreach ($row as $col => $val) {
                $cleanCol = strtolower(trim((string) $col));

                $shouldMask = false;
                if ($specificColumns !== null) {
                    $shouldMask = in_array($col, $specificColumns);
                } else {
                    $shouldMask = $this->isPiiColumn($cleanCol) || $this->containsPiiPattern($val);
                }

                if ($shouldMask && ! empty($val) && $val !== '-') {
                    $maskedVal = $this->maskValue($val, $cleanCol);
                    if ($maskedVal !== $val) {
                        $maskedCount++;
                        $columnsMasked[$col] = true;
                    }
                    $newRow[$col] = $maskedVal;
                } else {
                    $newRow[$col] = $val;
                }
            }
            $updatedRows[] = $newRow;
        }

        return [
            'rows' => $updatedRows,
            'masked_count' => $maskedCount,
            'columns_masked' => array_keys($columnsMasked),
        ];
    }

    protected function isPiiColumn(string $col): bool
    {
        $piiTerms = ['nik', 'ktp', 'email', 'phone', 'telepon', 'hp', 'handphone', 'wa', 'whatsapp', 'credit_card', 'card_number', 'ssn', 'password'];
        foreach ($piiTerms as $term) {
            if (str_contains($col, $term)) {
                return true;
            }
        }

        return false;
    }

    protected function containsPiiPattern(mixed $val): bool
    {
        if (! is_string($val)) {
            return false;
        }

        // Email regex
        if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        // 16-digit NIK or 10-14 digit Phone number
        $cleanDigits = preg_replace('/[^0-9]/', '', $val);
        if (strlen($cleanDigits) === 16 || (strlen($cleanDigits) >= 10 && strlen($cleanDigits) <= 14 && (str_starts_with($cleanDigits, '08') || str_starts_with($cleanDigits, '62')))) {
            return true;
        }

        return false;
    }

    public function maskValue(mixed $val, string $context = ''): string
    {
        if (is_null($val) || $val === '-' || $val === '') {
            return (string) $val;
        }

        $str = (string) $val;

        // Mask Email: johndoe@example.com -> j***e@example.com
        if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $str);
            $name = $parts[0];
            $domain = $parts[1] ?? '';
            $len = strlen($name);
            if ($len <= 2) {
                $maskedName = substr($name, 0, 1).'***';
            } else {
                $maskedName = substr($name, 0, 1).str_repeat('*', max(3, $len - 2)).substr($name, -1);
            }

            return $maskedName.'@'.$domain;
        }

        // Mask NIK (16 digit): 3275072512061004 -> 3275********1004
        $digits = preg_replace('/[^0-9]/', '', $str);
        if (strlen($digits) === 16 || str_contains($context, 'nik') || str_contains($context, 'ktp')) {
            if (strlen($str) >= 8) {
                return substr($str, 0, 4).'********'.substr($str, -4);
            }

            return '********';
        }

        // Mask Phone: 081234567890 -> 0812****7890
        if (str_contains($context, 'phone') || str_contains($context, 'hp') || str_contains($context, 'handphone') || str_contains($context, 'wa') || (strlen($digits) >= 10 && strlen($digits) <= 14)) {
            if (strlen($str) >= 7) {
                return substr($str, 0, 4).'****'.substr($str, -3);
            }

            return '****';
        }

        // Generic text masking
        $len = strlen($str);
        if ($len <= 3) {
            return '***';
        }

        return substr($str, 0, 1).str_repeat('*', $len - 2).substr($str, -1);
    }
}

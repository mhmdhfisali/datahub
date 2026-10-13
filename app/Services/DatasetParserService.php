<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Symfony\Component\HttpFoundation\File\File;

class DatasetParserService
{
    public function parse(mixed $fileInput = null, mixed $manualPayload = null): array
    {
        $payload = [];
        $fileType = null;
        $fileSizeBytes = null;
        $error = null;

        // 1. Process Excel / CSV File
        if (! empty($fileInput)) {
            if (is_array($fileInput)) {
                $fileInput = reset($fileInput);
            }

            $resolvedPath = $this->resolveFilePath($fileInput);

            if ($resolvedPath && file_exists($resolvedPath)) {
                $fileSizeBytes = filesize($resolvedPath);
                $ext = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
                $fileType = in_array($ext, ['xlsx', 'xls', 'csv']) ? $ext : 'xlsx';

                try {
                    $readerType = IOFactory::identify($resolvedPath);
                    $reader = IOFactory::createReader($readerType);
                    $reader->setReadDataOnly(true);

                    // Autodeteksi delimiter dan encoding UTF-8 untuk CSV
                    if ($reader instanceof Csv) {
                        $reader->setInputEncoding('UTF-8');
                        $handle = @fopen($resolvedPath, 'r');
                        if ($handle) {
                            $firstLine = fgets($handle);
                            fclose($handle);
                            if ($firstLine) {
                                $semicolons = substr_count($firstLine, ';');
                                $commas = substr_count($firstLine, ',');
                                $tabs = substr_count($firstLine, "\t");
                                if ($semicolons > $commas && $semicolons > $tabs) {
                                    $reader->setDelimiter(';');
                                } elseif ($tabs > $commas && $tabs > $semicolons) {
                                    $reader->setDelimiter("\t");
                                } else {
                                    $reader->setDelimiter(',');
                                }
                            }
                        }
                    }

                    $spreadsheet = $reader->load($resolvedPath);
                    $worksheet = $spreadsheet->getActiveSheet();

                    $highestColumn = $worksheet->getHighestDataColumn();
                    $highestRow = $worksheet->getHighestDataRow();

                    if ($highestRow >= 1 && ! empty($highestColumn)) {
                        // Membaca rentang persis A1:{highestColumn}{highestRow} untuk menjaga 100% urutan kolom A sampai terakhir
                        $rows = $worksheet->rangeToArray(
                            'A1:'.$highestColumn.$highestRow,
                            null,
                            true,
                            false,
                            false
                        );

                        if (! empty($rows) && count($rows) > 0) {
                            // Heuristic Header Auto-Detection:
                            // Jika ada baris judul/kop di baris-baris awal (misal baris 1-2 berisi judul umum),
                            // pindai hingga 10 baris pertama untuk menemukan baris header sejati.
                            $headerRowIndex = 0;
                            $bestHeaderScore = -1;
                            $candidateLimit = min(10, count($rows));

                            $headerKeywords = [
                                'no', 'nomor', 'num', 'number', 'id', 'nik', 'nisn', 'nip', 'nama', 'name',
                                'alamat', 'address', 'hp', 'telepon', 'phone', 'telp', 'email', 'kontak',
                                'kota', 'city', 'kabupaten', 'provinsi', 'kategori', 'status', 'jk', 'gender',
                                'jenis_kelamin', 'tgl', 'tanggal', 'date', 'lahir', 'umur', 'age', 'agama',
                                'pekerjaan', 'jabatan', 'divisi', 'rt', 'rw', 'kode', 'pos', 'keterangan',
                            ];

                            for ($rIdx = 0; $rIdx < $candidateLimit; $rIdx++) {
                                $candidateRow = $rows[$rIdx];
                                $nonEmptyCells = 0;
                                $keywordMatches = 0;
                                $stringCells = 0;

                                foreach ($candidateRow as $cell) {
                                    $cellStr = trim((string) $cell);
                                    if ($cellStr !== '') {
                                        $nonEmptyCells++;
                                        if (! is_numeric($cellStr)) {
                                            $stringCells++;
                                        }
                                        $cellLower = strtolower(preg_replace('/[^a-zA-Z0-9]+/u', ' ', $cellStr));
                                        $words = explode(' ', $cellLower);
                                        foreach ($headerKeywords as $kw) {
                                            if (in_array($kw, $words) || str_contains($cellLower, $kw)) {
                                                $keywordMatches++;
                                                break;
                                            }
                                        }
                                    }
                                }

                                // Skor kombinasi: kecocokan keyword header (bobot 5) + jumlah kolom teks terisi
                                $score = ($keywordMatches * 5) + ($nonEmptyCells >= 2 ? $nonEmptyCells : 0);
                                if ($score > $bestHeaderScore && $nonEmptyCells >= 2) {
                                    $bestHeaderScore = $score;
                                    $headerRowIndex = $rIdx;
                                }
                            }

                            // Buang baris kop di atas baris header jika ditemukan
                            if ($headerRowIndex > 0) {
                                array_splice($rows, 0, $headerRowIndex);
                            }

                            $rawHeaders = array_shift($rows);

                            // Deteksi kolom yang benar-benar memiliki header atau data (abaikan kolom kosong seperti kolom A kosong)
                            $validColumnIndices = [];
                            foreach ($rawHeaders as $colIdx => $rawHeader) {
                                $hasHeader = trim((string) $rawHeader) !== '';
                                if ($hasHeader) {
                                    $validColumnIndices[] = $colIdx;
                                } else {
                                    $hasData = false;
                                    foreach ($rows as $r) {
                                        if (isset($r[$colIdx]) && trim((string) $r[$colIdx]) !== '') {
                                            $hasData = true;
                                            break;
                                        }
                                    }
                                    if ($hasData) {
                                        $validColumnIndices[] = $colIdx;
                                    }
                                }
                            }

                            // Normalisasi nama header kolom tanpa mengubah urutan indeks valid
                            $orderedHeaders = [];
                            $seen = [];
                            $headerDisplayCounter = 1;
                            foreach ($validColumnIndices as $colIdx) {
                                $raw = $rawHeaders[$colIdx] ?? '';
                                $clean = trim((string) $raw);
                                // Bersihkan tanda kurung beserta isinya
                                $clean = preg_replace('/\s*\(.*?\)\s*/u', ' ', $clean);
                                // Konversi karakter khusus dan spasi ke underscore
                                $clean = preg_replace('/[^a-zA-Z0-9]+/u', '_', $clean);
                                $clean = strtolower(trim($clean, '_'));

                                if (empty($clean)) {
                                    $clean = 'column_'.$headerDisplayCounter;
                                }

                                // Tambahkan akhiran unik jika nama kolom duplikat (misal: nama_1, nama_2)
                                $colName = $clean;
                                if (isset($seen[$colName])) {
                                    $counter = 1;
                                    while (isset($seen["{$clean}_{$counter}"])) {
                                        $counter++;
                                    }
                                    $colName = "{$clean}_{$counter}";
                                }
                                $seen[$colName] = true;
                                $orderedHeaders[$colIdx] = $colName;
                                $headerDisplayCounter++;
                            }

                            // Memasukkan setiap baris data sesuai urutan kolom $orderedHeaders secara presisi
                            foreach ($rows as $row) {
                                $hasValue = false;
                                foreach ($row as $cell) {
                                    if (! is_null($cell) && trim((string) $cell) !== '') {
                                        $hasValue = true;
                                        break;
                                    }
                                }
                                if (! $hasValue) {
                                    continue;
                                }

                                $rowData = [];
                                foreach ($orderedHeaders as $colIdx => $colKey) {
                                    $cellVal = $row[$colIdx] ?? null;
                                    $rowData[$colKey] = is_null($cellVal) ? '-' : (is_string($cellVal) ? trim($cellVal) : $cellVal);
                                }
                                if (! empty($rowData)) {
                                    $payload[] = $rowData;
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $error = "Error parsing file: {$e->getMessage()}";
                    Log::error("DatasetParserService failure: {$e->getMessage()}", [
                        'file' => $resolvedPath,
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        // 2. Fallback Manual JSON Payload
        if (empty($payload) && ! empty($manualPayload)) {
            $fileType = 'json';
            if (is_string($manualPayload)) {
                if (str_contains($manualPayload, '[object Object]')) {
                    $error = 'Payload mengandung string invalid [object Object].';
                } else {
                    $fileSizeBytes = strlen($manualPayload);
                    $decoded = json_decode($manualPayload, true);
                    if (is_array($decoded)) {
                        $payload = $this->normalizeArrayPayload($decoded);
                    } else {
                        $error = 'Format JSON manual tidak valid.';
                    }
                }
            } elseif (is_array($manualPayload)) {
                $payload = $this->normalizeArrayPayload($manualPayload);
                $fileSizeBytes = strlen(json_encode($manualPayload));
            }
        }

        $recordCount = count($payload);
        $colCount = 0;
        if ($recordCount > 0 && isset($payload[0]) && is_array($payload[0])) {
            $colCount = count(array_keys($payload[0]));
        }

        return [
            'payload' => $payload,
            'record_count' => $recordCount,
            'column_count' => $colCount,
            'file_type' => $fileType,
            'file_size_bytes' => $fileSizeBytes,
            'error' => $error,
        ];
    }

    public function resolveFilePath(mixed $fileInput): ?string
    {
        if (is_array($fileInput)) {
            $fileInput = reset($fileInput);
        }

        if ($fileInput instanceof TemporaryUploadedFile || $fileInput instanceof File) {
            return $fileInput->getRealPath();
        }

        if (! is_string($fileInput)) {
            return null;
        }

        $candidates = [
            Storage::disk('local')->path($fileInput),
            Storage::disk('public')->path($fileInput),
            Storage::disk('local')->path('excel-imports/'.$fileInput),
            Storage::disk('public')->path('excel-imports/'.$fileInput),
            storage_path('app/public/'.$fileInput),
            storage_path('app/'.$fileInput),
            storage_path('app/private/'.$fileInput),
            storage_path('app/excel-imports/'.$fileInput),
            storage_path('app/private/excel-imports/'.$fileInput),
            storage_path('app/livewire-tmp/'.$fileInput),
            storage_path('app/private/livewire-tmp/'.$fileInput),
            $fileInput,
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        $filename = basename($fileInput);
        $searchDirs = [
            Storage::disk('local')->path('excel-imports'),
            Storage::disk('local')->path(''),
            storage_path('app/private/excel-imports'),
            storage_path('app/excel-imports'),
            storage_path('app/public/excel-imports'),
            storage_path('app/private/livewire-tmp'),
            storage_path('app/livewire-tmp'),
        ];

        foreach ($searchDirs as $dir) {
            if (is_dir($dir)) {
                $matches = glob($dir.'/*'.$filename.'*');
                if (! empty($matches)) {
                    return $matches[0];
                }
            }
        }

        return null;
    }

    protected function normalizeArrayPayload(array $data): array
    {
        if (! empty($data) && ! isset($data[0])) {
            $data = [$data];
        }

        $normalized = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                continue;
            }

            $row = [];
            $seenKeys = [];
            foreach ($item as $k => $v) {
                $clean = trim((string) $k);
                $clean = preg_replace('/\s*\(.*?\)\s*/u', ' ', $clean);
                $clean = preg_replace('/[^a-zA-Z0-9]+/u', '_', $clean);
                $clean = strtolower(trim($clean, '_'));
                if (empty($clean)) {
                    $clean = 'field';
                }

                $keyName = $clean;
                if (isset($seenKeys[$keyName])) {
                    $counter = 1;
                    while (isset($seenKeys["{$clean}_{$counter}"])) {
                        $counter++;
                    }
                    $keyName = "{$clean}_{$counter}";
                }
                $seenKeys[$keyName] = true;
                $row[$keyName] = is_null($v) ? '-' : (is_string($v) ? trim($v) : $v);
            }
            if (! empty($row)) {
                $normalized[] = $row;
            }
        }

        return $normalized;
    }
}

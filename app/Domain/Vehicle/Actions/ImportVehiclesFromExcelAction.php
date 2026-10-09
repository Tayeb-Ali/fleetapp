<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Actions;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\LegacyImporter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import / sync vehicles from the V2 Excel tracker (VTTR sheet).
 *
 * Single end-to-end use case (Decision Gate Q1 → Action, Eloquent directly).
 * Header row is detected (contains "Chassis No."), data rows after it.
 * Only the 16 Excel columns are synced — financial columns
 * (cost/duty/price/paid/...) are never nulled by the sync.
 */
class ImportVehiclesFromExcelAction
{
    /**
     * @return array{imported:int,skipped:int,errors:array<int,string>}
     */
    public function execute(string $path, bool $dryRun = false, ?callable $progress = null): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException("Excel file not found: {$path}");
        }

        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName('VTTR') ?? $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headerRow = $this->detectHeaderRow($rows);
        $map = $this->buildColumnMap($rows[$headerRow] ?? []);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber <= $headerRow) {
                continue;
            }

            $payload = $this->mapRow($row, $map);

            if ($progress) {
                $progress();
            }

            if ($payload === null) {
                $skipped++;

                continue;
            }

            $validationError = $this->validatePayload($payload);
            if ($validationError !== null) {
                $skipped++;
                $errors[] = "Row {$rowNumber}: {$validationError}";

                continue;
            }

            if ($dryRun) {
                $imported++;

                continue;
            }

            try {
                $this->upsert($payload);
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Row {$rowNumber} (VIN ".($payload['vin'] ?? '?').'): '.$e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     */
    protected function detectHeaderRow(array $rows): int
    {
        foreach ($rows as $number => $row) {
            if ($number > 20) {
                break;
            }
            foreach ((array) $row as $cell) {
                if (is_string($cell) && stripos($cell, 'Chassis No.') !== false) {
                    return (int) $number;
                }
            }
        }

        return 9;
    }

    /**
     * @param  array<string,mixed>  $header
     * @return array<string,string> excelCol => canonicalColumn
     */
    protected function buildColumnMap(array $header): array
    {
        $normalize = fn ($v): string => strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $v)));
        $byName = [];
        foreach ($header as $col => $title) {
            $byName[$normalize($title)] = (string) $col;
        }

        $find = function (array $needles) use ($byName): ?string {
            foreach ($needles as $needle) {
                foreach ($byName as $title => $col) {
                    if (str_contains($title, $needle)) {
                        return $col;
                    }
                }
            }

            return null;
        };

        $pairs = [
            'brand' => ['brand name'],
            'model' => ['vehicle type', 'model'],
            'fuel' => ['fuel'],
            'mod' => ['modified'],
            'warehouse_location' => ['current location'],
            'vin' => ['chassis'],
            'pol' => ['pol', 'port of loading'],
            'pod' => ['pod', 'port of discharge'],
            'voyage_no' => ['voyage'],
            'destination' => ['final destination'],
            'shipped_at' => ['shipping date'],
            'bl' => ['b/l'],
            'arrived_at' => ['arrival date'],
            'buyer_name' => ['client'],
            'remarks' => ['remarks'],
            'status' => ['status'],
        ];

        $map = [];
        foreach ($pairs as $canonical => $needles) {
            $col = $find($needles);
            if ($col !== null) {
                $map[$col] = $canonical;
            }
        }

        return $map;
    }

    /**
     * @param  array<string,mixed>  $row
     * @param  array<string,string>  $map
     * @return array<string,mixed>|null null when the row is empty
     */
    protected function mapRow(array $row, array $map): ?array
    {
        $byCanonical = [];
        foreach ($map as $excelCol => $canonical) {
            $byCanonical[$canonical] = $row[$excelCol] ?? null;
        }

        $clean = fn ($v): string => mb_substr(trim((string) ($v ?? '')), 0, 300);

        $vin = $clean($byCanonical['vin'] ?? '');
        // Skip fully empty rows (the 27 gaps in V2).
        $hasAny = $vin !== '';
        foreach (['brand', 'model', 'bl', 'buyer_name', 'remarks', 'status'] as $key) {
            if (trim((string) ($byCanonical[$key] ?? '')) !== '') {
                $hasAny = true;
                break;
            }
        }
        if (! $hasAny) {
            return null;
        }

        if ($vin === '') {
            return ['vin' => '', '_error' => 'Missing Chassis No. (VIN)'];
        }

        $statusMap = LegacyImporter::statusMap();
        $status = $clean($byCanonical['status'] ?? '');
        $stage = $statusMap[$status] ?? 'purchased';

        return [
            'vin' => $vin,
            'brand' => $clean($byCanonical['brand'] ?? '') ?: null,
            'model' => $clean($byCanonical['model'] ?? '') ?: null,
            'fuel' => $clean($byCanonical['fuel'] ?? '') ?: null,
            'mod' => $clean($byCanonical['mod'] ?? '') ?: null,
            'warehouse_location' => $clean($byCanonical['warehouse_location'] ?? '') ?: null,
            'pol' => $clean($byCanonical['pol'] ?? '') ?: null,
            'pod' => $clean($byCanonical['pod'] ?? '') ?: null,
            'voyage_no' => $clean($byCanonical['voyage_no'] ?? '') ?: null,
            'destination' => $clean($byCanonical['destination'] ?? '') ?: null,
            'shipped_at' => $this->toDate($byCanonical['shipped_at'] ?? null),
            'bl' => $clean($byCanonical['bl'] ?? '') ?: null,
            'arrived_at' => $this->toDate($byCanonical['arrived_at'] ?? null),
            'buyer_name' => $clean($byCanonical['buyer_name'] ?? '') ?: null,
            'remarks' => mb_substr(trim((string) ($byCanonical['remarks'] ?? '')), 0, 2000) ?: null,
            'stage' => $stage,
        ];
    }

    protected function validatePayload(array $payload): ?string
    {
        if (($payload['vin'] ?? '') === '') {
            return $payload['_error'] ?? 'Missing Chassis No. (VIN)';
        }

        return null;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    protected function upsert(array $payload): void
    {
        unset($payload['_error']);
        $vin = $payload['vin'];
        unset($payload['vin']);

        // Never null financial/logistics columns that Excel does not own.
        $payload = array_filter($payload, fn ($v) => $v !== null);

        if ($payload === []) {
            return;
        }

        Vehicle::query()->updateOrCreate(['vin' => $vin], $payload);
    }

    protected function toDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel serial (e.g. 45758) → Y-m-d.
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $ts = strtotime($text);

        return $ts === false ? null : date('Y-m-d', $ts);
    }
}

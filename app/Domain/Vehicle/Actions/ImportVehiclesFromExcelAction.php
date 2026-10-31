<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Actions;

use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\FinancialFieldsResolver;
use App\Domain\Vehicle\Services\LegacyImporter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import / sync vehicles from the V3 Excel tracker (VTTR sheet).
 *
 * The tracker has three columns literally named "Currency" (purchase,
 * shipping, sale), so the map is positional: the first occurrence belongs to
 * purchase, the second to shipping and the third to sale.
 *
 * Financial columns are synced when present; empty cells never null existing
 * values. Computed base amounts are compared with the sheet's "In USD" and
 * "Total Cost" columns and differences are reported as warnings.
 */
class ImportVehiclesFromExcelAction
{
    /** @var array<int, string> */
    private array $warnings = [];

    /**
     * @return array{imported:int,skipped:int,errors:array<int,string>,warnings:array<int,string>}
     */
    public function execute(string $path, bool $dryRun = false, ?callable $progress = null): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException("Excel file not found: {$path}");
        }

        $this->warnings = [];

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
                $this->upsert($payload, (int) $rowNumber);
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Row {$rowNumber} (VIN ".($payload['vin'] ?? '?').'): '.$e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'warnings' => $this->warnings];
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
     * Positional column map (handles the three duplicate "Currency" headers).
     *
     * @param  array<string,mixed>  $header
     * @return array<string,string> excelCol => canonicalColumn
     */
    protected function buildColumnMap(array $header): array
    {
        $ordered = [];
        foreach ($header as $col => $title) {
            $normalized = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $title)));
            if ($normalized !== '') {
                $ordered[] = [(string) $col, $normalized];
            }
        }

        $map = [];

        $assign = function (string $canonical, array $needles, int $occurrence = 0) use (&$map, $ordered): void {
            $matches = [];
            foreach ($ordered as [$col, $title]) {
                foreach ($needles as $needle) {
                    if (str_contains($title, $needle)) {
                        $matches[] = $col;
                        break;
                    }
                }
            }

            if (isset($matches[$occurrence])) {
                $map[$matches[$occurrence]] = $canonical;
            }
        };

        $assign('brand', ['brand name']);
        $assign('model', ['vehicle type', 'model']);
        $assign('fuel', ['fuel']);
        $assign('purchased_at', ['purchase date']);
        $assign('cost', ['purchase value']);
        $assign('purchase_currency', ['currency'], 0);
        $assign('_in_usd', ['in usd']);
        $assign('mod', ['modified']);
        $assign('warehouse_location', ['current location']);
        $assign('vin', ['chassis']);
        $assign('pol', ['pol', 'port of loading']);
        $assign('pod', ['pod', 'port of discharge']);
        $assign('voyage_no', ['voyage']);
        $assign('destination', ['final destination']);
        $assign('bl', ['b/l', 'bill of lading']);
        $assign('shipped_at', ['shipping date']);
        $assign('arrived_at', ['arrival date']);
        $assign('shipping_cost', ['shipping cost']);
        $assign('shipping_currency', ['currency'], 1);
        $assign('landed_cost', ['landed cost']);
        $assign('modification_cost', ['modification cost']);
        $assign('_total_cost', ['total cost']);
        $assign('buyer_name', ['client']);
        $assign('sold_at', ['sales date']);
        $assign('sale_price', ['sales value']);
        $assign('sale_currency', ['currency'], 2);
        $assign('remarks', ['remarks']);
        $assign('_status', ['status']);

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
        // Skip fully empty rows (the 27 gaps in the tracker).
        $hasAny = $vin !== '';
        foreach (['brand', 'model', 'bl', 'buyer_name', 'remarks', '_status'] as $key) {
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

        $status = $clean($byCanonical['_status'] ?? '');
        $stage = LegacyImporter::stageForStatus($status);

        $shippedAt = $this->toDate($byCanonical['shipped_at'] ?? null);
        $bl = $clean($byCanonical['bl'] ?? '') ?: null;

        if ($stage === null) {
            $stage = $shippedAt !== null || $bl !== null ? 'shipped' : 'purchased';
        }

        $resolver = app(FinancialFieldsResolver::class);
        $mod = $resolver->normalizeMod($byCanonical['mod'] ?? null);

        return [
            'vin' => $vin,
            'brand' => $clean($byCanonical['brand'] ?? '') ?: null,
            'model' => $clean($byCanonical['model'] ?? '') ?: null,
            'fuel' => $clean($byCanonical['fuel'] ?? '') ?: null,
            'mod' => $mod,
            'warehouse_location' => $clean($byCanonical['warehouse_location'] ?? '') ?: null,
            'purchased_at' => $this->toDate($byCanonical['purchased_at'] ?? null),
            'cost' => $this->toNumber($byCanonical['cost'] ?? null),
            'purchase_currency' => $this->normalizeCurrency($byCanonical['purchase_currency'] ?? null),
            'pol' => $clean($byCanonical['pol'] ?? '') ?: null,
            'pod' => $clean($byCanonical['pod'] ?? '') ?: null,
            'voyage_no' => $clean($byCanonical['voyage_no'] ?? '') ?: null,
            'destination' => $clean($byCanonical['destination'] ?? '') ?: null,
            'bl' => $bl,
            'shipped_at' => $shippedAt,
            'arrived_at' => $this->toDate($byCanonical['arrived_at'] ?? null),
            'shipping_cost' => $this->toNumber($byCanonical['shipping_cost'] ?? null),
            'shipping_currency' => $this->normalizeCurrency($byCanonical['shipping_currency'] ?? null),
            'landed_cost' => $this->toNumber($byCanonical['landed_cost'] ?? null),
            'modification_cost' => $mod === 'Yes' ? $this->toNumber($byCanonical['modification_cost'] ?? null) : null,
            'buyer_name' => $clean($byCanonical['buyer_name'] ?? '') ?: null,
            'sold_at' => $this->toDate($byCanonical['sold_at'] ?? null),
            'sale_price' => $this->toNumber($byCanonical['sale_price'] ?? null),
            'sale_currency' => $this->normalizeCurrency($byCanonical['sale_currency'] ?? null),
            'remarks' => mb_substr(trim((string) ($byCanonical['remarks'] ?? '')), 0, 2000) ?: null,
            'stage' => $stage,
            '_in_usd' => $this->toNumber($byCanonical['_in_usd'] ?? null),
            '_total_cost' => $this->toNumber($byCanonical['_total_cost'] ?? null),
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
    protected function upsert(array $payload, int $rowNumber = 0): void
    {
        unset($payload['_error']);

        $vin = (string) $payload['vin'];
        unset($payload['vin']);

        $inUsd = $payload['_in_usd'] ?? null;
        $excelTotal = $payload['_total_cost'] ?? null;
        unset($payload['_in_usd'], $payload['_total_cost']);

        // Auto-create currencies the client typed in the sheet (with a warning).
        $currencies = app(CurrencyService::class);
        foreach (['purchase_currency', 'shipping_currency', 'sale_currency'] as $key) {
            $code = $payload[$key] ?? null;
            if ($code === null) {
                continue;
            }

            $currency = $currencies->ensure($code);
            if ($currency !== null && $currency->wasRecentlyCreated) {
                $this->warnings[] = __('import.warnings.currency_created', [
                    'row' => $rowNumber,
                    'vin' => $vin,
                    'code' => $code,
                ]);
            }
        }

        // Currency + FX rate + converted base amounts.
        $payload = app(FinancialFieldsResolver::class)->apply($payload);

        $purchaseBase = $payload['purchase_base_amount'] ?? null;
        if (is_numeric($inUsd) && is_numeric($purchaseBase) && abs((float) $inUsd - (float) $purchaseBase) > 0.5) {
            $this->warnings[] = __('import.warnings.in_usd_mismatch', [
                'row' => $rowNumber,
                'vin' => $vin,
                'excel' => $inUsd,
                'computed' => $purchaseBase,
            ]);
        }

        if (is_numeric($excelTotal)) {
            $unconverted = false;
            foreach ([['cost', 'purchase_base_amount'], ['shipping_cost', 'shipping_base_amount']] as [$native, $base]) {
                if (($payload[$native] ?? null) !== null && ($payload[$base] ?? null) === null) {
                    $unconverted = true;
                }
            }

            $computed = (float) ($payload['purchase_base_amount'] ?? 0)
                + (float) ($payload['shipping_base_amount'] ?? 0)
                + (float) ($payload['landed_cost'] ?? 0)
                + (float) ($payload['modification_cost'] ?? 0);

            if (! $unconverted && abs((float) $excelTotal - $computed) > 1.0) {
                $this->warnings[] = __('import.warnings.total_mismatch', [
                    'row' => $rowNumber,
                    'vin' => $vin,
                    'excel' => $excelTotal,
                    'computed' => $computed,
                ]);
            }
        }

        // Never null existing values with empty cells.
        $payload = array_filter($payload, fn ($v) => $v !== null);

        if ($payload === []) {
            return;
        }

        // Restore soft-deleted VINs instead of failing / duplicating.
        $vehicle = Vehicle::withTrashed()->firstOrNew(['vin' => $vin]);
        $vehicle->fill($payload);
        $vehicle->deleted_at = null;
        $vehicle->save();
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

    protected function toNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function normalizeCurrency(mixed $value): ?string
    {
        $code = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) ($value ?? '')));

        return $code === '' ? null : substr($code, 0, 8);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Services;

use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy importer — reads ../fleet-tracker/seed.json (120 records) and/or
 * a legacy SQLite DB (fleet-v2.db) into the Laravel vehicles table.
 *
 * Defensive: filters by Schema::hasColumn / fillable so it works even when
 * the Vehicle model columns differ from the legacy payload.
 */
class LegacyImporter
{
    /** OLD status => new stage (legacy server.js OLD map + V3 tracker). */
    public static function statusMap(): array
    {
        return [
            'Not shipped' => 'purchased',
            'Not Shipped Yet' => 'purchased',
            'Yet to be received' => 'shipped',
            'Shipped / Under Processing' => 'shipped',
            'Ready for Sale' => 'instock',
            'Ready for delivery from Sawakin' => 'instock',
            'At Free Zone, Ready For Sale' => 'instock',
            'Sold' => 'sold',
            'Sold, client to complete processing' => 'sold',
            'Sold & Delivered' => 'delivered',
        ];
    }

    /** Resolve a tracker status (case/space tolerant) to a stage. */
    public static function stageForStatus(?string $status): ?string
    {
        $status = strtolower(trim((string) $status));
        if ($status === '') {
            return null;
        }

        foreach (self::statusMap() as $label => $stage) {
            if (strtolower(trim($label)) === $status) {
                return $stage;
            }
        }

        // Contains fallback for statuses with extra qualifiers.
        foreach (self::statusMap() as $label => $stage) {
            $needle = strtolower(trim($label));
            if ($needle !== '' && str_contains($status, $needle)) {
                return $stage;
            }
        }

        return null;
    }

    public static function validStages(): array
    {
        return ['purchased', 'shipped', 'arrived', 'clearing', 'cleared', 'instock', 'sold', 'delivered'];
    }

    /** Map a legacy record (seed.json or sqlite row) to a vehicles-table payload. */
    public static function mapRecord(array $record): ?array
    {
        $clean = function (mixed $v): string {
            return mb_substr(trim((string) ($v ?? '')), 0, 300);
        };

        $vin = $clean($record['vin'] ?? '');
        if ($vin === '') {
            return null;
        }

        $map = self::statusMap();
        $stage = $clean($record['stage'] ?? '');
        if (! in_array($stage, self::validStages(), true)) {
            $stage = $map[$record['status'] ?? ''] ?? 'purchased';
        }

        $keys = ['brand', 'model', 'fuel', 'mod', 'pdate', 'supplier', 'cost', 'bl', 'bl_release', 'voy', 'pol', 'pod', 'dest', 'ship', 'arr', 'cust_no', 'cust_date', 'duty', 'loc', 'in_date', 'ord_no', 'client', 'phone', 'price', 'paid', 'sale_date', 'recv', 'del_date', 'remarks',
            // New financial columns (kept when the source already uses the new schema).
            'purchased_at', 'purchase_currency', 'purchase_fx_rate', 'purchase_base_amount',
            'shipped_at', 'shipping_cost', 'shipping_currency', 'shipping_fx_rate', 'shipping_base_amount',
            'landed_cost', 'modification_cost',
            'sold_at', 'sale_price', 'sale_currency', 'sale_fx_rate', 'sale_base_amount', 'amount_paid',
        ];

        $payload = ['vin' => $vin, 'stage' => $stage];
        foreach ($keys as $k) {
            if (array_key_exists($k, $record)) {
                $payload[$k] = $clean($record[$k]);
            }
        }

        // Also populate canonical columns so the new schema (voyage_no, buyer_name, ...)
        // receives data even though the legacy payload uses short keys.
        try {
            $modelClass = Vehicle::class;
            if (class_exists($modelClass) && defined("$modelClass::LEGACY_MAP")) {
                foreach ($modelClass::LEGACY_MAP as $legacy => $column) {
                    if (isset($payload[$legacy]) && ! isset($payload[$column])) {
                        $payload[$column] = $payload[$legacy];
                    }
                    // Legacy sqlite already in canonical form? mirror back to legacy for BC.
                    if (isset($record[$column]) && ! isset($payload[$legacy])) {
                        $payload[$legacy] = $clean($record[$column]);
                    }
                    if (isset($record[$column]) && ! isset($payload[$column])) {
                        $payload[$column] = $clean($record[$column]);
                    }
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return $payload;
    }

    /** Keep only columns that exist + respect Vehicle fillable guard. */
    protected static function guard(array $payload): array
    {
        try {
            if (Schema::hasTable('vehicles')) {
                $columns = Schema::getColumnListing('vehicles');
                $payload = array_filter($payload, fn ($k) => in_array($k, $columns, true), ARRAY_FILTER_USE_KEY);
            }
        } catch (\Throwable) {
            // keep payload as-is
        }

        try {
            $modelClass = Vehicle::class;
            if (class_exists($modelClass)) {
                $fillable = (new $modelClass)->getFillable();
                if ($fillable !== []) {
                    // Always allow vin (primary key) for lookup even if not fillable.
                    $allowed = array_merge($fillable, ['vin']);
                    $payload = array_filter($payload, fn ($k) => in_array($k, $allowed, true), ARRAY_FILTER_USE_KEY);
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return $payload;
    }

    protected static function upsert(array $payload): bool
    {
        try {
            $modelClass = Vehicle::class;
            if (class_exists($modelClass) && method_exists($modelClass, 'translateLegacyKeys')) {
                $payload = $modelClass::translateLegacyKeys($payload);
            }
        } catch (\Throwable) {
            // ignore
        }

        $payload = self::guard($payload);
        $vin = $payload['vin'] ?? null;
        if (! $vin) {
            return false;
        }
        unset($payload['vin']);

        if ($payload === []) {
            return false;
        }

        $modelClass = Vehicle::class;
        if (class_exists($modelClass)) {
            $modelClass::query()->updateOrCreate(['vin' => $vin], $payload);

            return true;
        }

        if (Schema::hasTable('vehicles')) {
            if (DB::table('vehicles')->where('vin', $vin)->exists()) {
                DB::table('vehicles')->where('vin', $vin)->update($payload);
            } else {
                DB::table('vehicles')->insert($payload + ['vin' => $vin]);
            }

            return true;
        }

        return false;
    }

    /**
     * @return array{imported:int,skipped:int}
     */
    public function importFromSeedJson(string $path, ?callable $progress = null): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException("Seed file not found: {$path}");
        }

        $raw = file_get_contents($path);
        $rows = json_decode((string) $raw, true);
        if (! is_array($rows)) {
            throw new \RuntimeException("Invalid seed JSON: {$path}");
        }

        $imported = 0;
        $skipped = 0;
        foreach ($rows as $record) {
            $mapped = self::mapRecord(is_array($record) ? $record : []);
            if (! $mapped) {
                $skipped++;
            } elseif (self::upsert($mapped)) {
                $imported++;
            } else {
                $skipped++;
            }
            if ($progress) {
                $progress();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * @return array{imported:int,skipped:int}
     */
    public function importFromSqlite(string $dbPath, ?callable $progress = null): array
    {
        if (! is_file($dbPath)) {
            throw new \RuntimeException("SQLite DB not found: {$dbPath}");
        }

        $pdo = new \PDO('sqlite:'.$dbPath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
        if (! in_array('vehicles', (array) $tables, true)) {
            throw new \RuntimeException("Table 'vehicles' not found in {$dbPath}");
        }

        $rows = $pdo->query('SELECT * FROM vehicles')->fetchAll(\PDO::FETCH_ASSOC);

        $imported = 0;
        $skipped = 0;
        foreach ($rows as $record) {
            $mapped = self::mapRecord(is_array($record) ? $record : []);
            if (! $mapped) {
                $skipped++;
            } elseif (self::upsert($mapped)) {
                $imported++;
            } else {
                $skipped++;
            }
            if ($progress) {
                $progress();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}

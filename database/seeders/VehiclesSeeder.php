<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Vehicles extracted from the client's "Vehicles Tracker-2026_V3.xlsx".
 *
 * The snapshot lives in database/seeders/data/vehicles-import.json and is
 * upserted by VIN, so running the seeder repeatedly is safe.
 */
class VehiclesSeeder extends Seeder
{
    /** Stay below SQLite's default 999 bound-parameter limit. */
    private const MAX_PARAMETERS = 900;

    public function run(): void
    {
        $path = database_path('seeders/data/vehicles-import.json');

        if (! is_file($path)) {
            return;
        }

        /** @var array{rows?: list<array<string, mixed>>} $snapshot */
        $snapshot = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        /** @var list<array<string, mixed>> $rows */
        $rows = $snapshot['rows'] ?? [];

        if ($rows === []) {
            return;
        }

        $columns = array_keys($rows[0]);
        $chunkSize = max(1, intdiv(self::MAX_PARAMETERS, max(1, count($columns))));
        $updateColumns = array_values(array_diff($columns, ['vin']));

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table('vehicles')->upsert($chunk, ['vin'], $updateColumns);
        }
    }
}

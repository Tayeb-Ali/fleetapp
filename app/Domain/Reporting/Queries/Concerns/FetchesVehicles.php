<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries\Concerns;

use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared vehicle fetching + legacy helpers (server.js lines 56-65, 81-85).
 *
 * Prefers App\Domain\Vehicle\Models\Vehicle when it exists, otherwise
 * falls back to DB::table('vehicles'). Returns [] when neither is
 * available so reports degrade gracefully (no vehicles migration yet).
 */
trait FetchesVehicles
{
    /** All vehicle rows as plain arrays. */
    protected function vehicles(): array
    {
        // 1) Domain model, when it exists.
        if (class_exists(Vehicle::class)) {
            $model = Vehicle::class;

            return $model::query()->get()
                ->map(fn ($v) => $v instanceof Model ? $v->toArray() : (array) $v)
                ->all();
        }

        // 2) Raw table fallback.
        try {
            if (Schema::hasTable('vehicles')) {
                return DB::table('vehicles')->get()
                    ->map(fn ($r) => (array) $r)
                    ->all();
            }
        } catch (\Throwable) {
            return [];
        }

        return [];
    }

    /** Days elapsed since a Y-m-d date (legacy dd()). '' when empty/invalid. */
    protected function daysSince(mixed $date): mixed
    {
        if ($date === null || $date === '') {
            return '';
        }
        try {
            $ts = strtotime((string) $date);
            if ($ts === false) {
                return '';
            }

            return max(0, (int) floor((time() - $ts) / 86400));
        } catch (\Throwable) {
            return '';
        }
    }

    /** Legacy nv(): cast numeric fields, keep everything else as-is. */
    protected function num(array $v, string $key): mixed
    {
        if (! in_array($key, ['cost', 'duty', 'price', 'paid'], true)) {
            return $v[$key] ?? '';
        }

        return ($v[$key] ?? '') === '' ? '' : (float) $v[$key];
    }

    protected function sum(array $rows, string $key): float
    {
        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float) ($r[$key] ?? 0);
        }

        return $total;
    }
}

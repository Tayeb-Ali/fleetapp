<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries\Concerns;

use App\Domain\Reporting\Support\ReportFilters;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\VehicleCostService;
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
    /** All vehicle rows as plain arrays, honouring the bound date filter. */
    protected function vehicles(): array
    {
        $rows = [];

        // 1) Domain model, when it exists.
        if (class_exists(Vehicle::class)) {
            $model = Vehicle::class;

            $rows = $model::query()->get()
                ->map(fn ($v) => $v instanceof Model ? $v->toArray() : (array) $v)
                ->all();
        } elseif (Schema::hasTable('vehicles')) {
            // 2) Raw table fallback.
            try {
                $rows = DB::table('vehicles')->get()
                    ->map(fn ($r) => (array) $r)
                    ->all();
            } catch (\Throwable) {
                return [];
            }
        }

        return app(ReportFilters::class)->apply($rows);
    }

    /**
     * Vehicle rows merged with their base-currency cost breakdown
     * (purchase_base, shipping_base, landed_base, modification_base,
     * total_cost, sale_base, profit, fx_missing).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function vehiclesWithCosts(): array
    {
        $service = app(VehicleCostService::class);

        return array_map(function (array $vehicle) use ($service): array {
            return array_merge($vehicle, $service->for($vehicle)->toArray());
        }, $this->vehicles());
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
        $numeric = [
            'cost', 'duty', 'price', 'paid',
            'shipping_cost', 'landed_cost', 'modification_cost',
            'purchase_fx_rate', 'shipping_fx_rate', 'sale_fx_rate',
            'purchase_base_amount', 'shipping_base_amount', 'sale_base_amount',
        ];

        if (! in_array($key, $numeric, true)) {
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

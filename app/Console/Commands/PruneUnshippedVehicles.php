<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Console\Command;

/**
 * Soft-delete stale vehicles that were registered but never shipped.
 *
 * A vehicle qualifies when it is still at the "purchased" stage, has no
 * shipping date and no Bill of Lading, and has been idle longer than the
 * configured number of months (based on the purchase date, falling back to
 * the record creation date). Sold or delivered vehicles are never touched.
 */
class PruneUnshippedVehicles extends Command
{
    protected $signature = 'fleet:prune-unshipped
        {--months=6 : Months of inactivity before a never-shipped vehicle is pruned}
        {--dry-run : List the vehicles that would be pruned without deleting them}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Soft-delete vehicles that never shipped and have been idle for months';

    public function handle(): int
    {
        $months = max(1, (int) $this->option('months'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subMonthsNoOverflow($months)->toDateString();

        $query = Vehicle::query()
            ->where('stage', VehicleStage::Purchased->value)
            ->whereNull('shipped_at')
            ->whereNull('bl')
            ->where(function ($q) use ($cutoff): void {
                $q->where('purchased_at', '<=', $cutoff)
                    ->orWhere(function ($q) use ($cutoff): void {
                        $q->whereNull('purchased_at')
                            ->where('created_at', '<=', $cutoff.' 00:00:00');
                    });
            })
            ->orderBy('vin');

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info(__('prune.none', ['months' => $months]));

            return self::SUCCESS;
        }

        $this->info(__('prune.found', ['count' => $count, 'months' => $months, 'cutoff' => $cutoff]));

        $rows = [];
        foreach ((clone $query)->get(['vin', 'brand', 'model', 'purchased_at', 'created_at']) as $vehicle) {
            $rows[] = [
                (string) $vehicle->vin,
                trim(((string) ($vehicle->brand ?? '')).' '.((string) ($vehicle->model ?? ''))),
                $this->dateOnly($vehicle->purchased_at),
                $this->dateOnly($vehicle->created_at),
            ];
        }

        $this->table(
            [__('prune.vin'), __('prune.vehicle'), __('prune.purchased_at'), __('prune.created_at')],
            $rows,
        );

        if ($dryRun) {
            $this->warn(__('prune.dry_run'));

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(__('prune.confirm', ['count' => $count]), false)) {
            $this->info(__('prune.cancelled'));

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ((clone $query)->reorder()->lazyById(200, 'vin') as $vehicle) {
            $vehicle->delete();
            $deleted++;
        }

        $this->info(__('prune.deleted', ['count' => $deleted]));

        return self::SUCCESS;
    }

    /** Date value as Y-m-d (casts may return a date string or a DateTime). */
    protected function dateOnly(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    }
}

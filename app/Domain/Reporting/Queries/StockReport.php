<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Legacy REP.stock — instock + sold (not delivered). */
class StockReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'stock';
    }

    public function titleKey(): string
    {
        return 'Current warehouse stock (available + sold, not delivered)';
    }

    public function columns(): array
    {
        return ['Brand', 'Model', 'Chassis No. (VIN)', 'Stage', 'Warehouse location', 'Warehouse entry date', 'Days in warehouse', 'Buyer / Organization', 'Sale order no.'];
    }

    public function rows(): array
    {
        return array_values(array_map(
            fn ($v) => [
                $v['brand'] ?? '',
                $v['model'] ?? '',
                $v['vin'] ?? '',
                $this->stageLabel($v['stage'] ?? null),
                $v['loc'] ?? '',
                $v['in_date'] ?? '',
                $this->daysSince($v['in_date'] ?? ($v['arr'] ?? '')),
                $v['client'] ?? '',
                $v['ord_no'] ?? '',
            ],
            array_filter($this->vehicles(), fn ($v) => in_array($v['stage'] ?? null, ['instock', 'sold'], true))
        ));
    }
}

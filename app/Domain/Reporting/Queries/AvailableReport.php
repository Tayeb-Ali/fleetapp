<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Legacy REP.available — instock only. */
class AvailableReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'available';
    }

    public function titleKey(): string
    {
        return 'Vehicles available for sale';
    }

    public function columns(): array
    {
        return ['Brand', 'Model', 'Chassis No. (VIN)', 'Fuel / Engine', 'Warehouse location', 'Warehouse entry date', 'Days in warehouse'];
    }

    public function rows(): array
    {
        return array_values(array_map(
            fn ($v) => [
                $v['brand'] ?? '',
                $v['model'] ?? '',
                $v['vin'] ?? '',
                $v['fuel'] ?? '',
                $v['loc'] ?? '',
                $v['in_date'] ?? '',
                $this->daysSince($v['in_date'] ?? ($v['arr'] ?? '')),
            ],
            array_filter($this->vehicles(), fn ($v) => ($v['stage'] ?? null) === 'instock')
        ));
    }
}

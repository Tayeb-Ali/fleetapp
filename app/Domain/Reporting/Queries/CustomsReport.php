<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Legacy REP.customs — arrived/clearing/cleared or customs_no present. */
class CustomsReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'customs';
    }

    public function titleKey(): string
    {
        return 'Customs & Clearance';
    }

    public function columns(): array
    {
        return ['Model', 'Chassis No. (VIN)', 'Bill of Lading No. (B/L)', 'Stage', 'Customs declaration no.', 'Clearance date', 'Customs duty'];
    }

    public function rows(): array
    {
        return array_values(array_map(
            fn ($v) => [
                $v['model'] ?? '',
                $v['vin'] ?? '',
                $v['bl'] ?? '',
                $this->stageLabel($v['stage'] ?? null),
                $v['cust_no'] ?? '',
                $v['cust_date'] ?? '',
                $this->num($v, 'duty'),
            ],
            array_filter(
                $this->vehicles(),
                fn ($v) => in_array($v['stage'] ?? null, ['arrived', 'clearing', 'cleared'], true) || ($v['cust_no'] ?? '') !== ''
            )
        ));
    }
}

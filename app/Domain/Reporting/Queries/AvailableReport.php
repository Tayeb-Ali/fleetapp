<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Legacy REP.available — instock only. */
class AvailableReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'available';
    }

    public function titleKey(): string
    {
        return 'reports.titles.available';
    }

    public function columns(): array
    {
        return [
            $this->field('brand'),
            $this->field('model'),
            $this->field('vin'),
            $this->field('fuel'),
            $this->field('loc'),
            $this->field('in_date'),
            $this->column('days_in_warehouse'),
        ];
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
                $this->formatDate($v['in_date'] ?? ''),
                $this->daysSince($v['in_date'] ?? ($v['arr'] ?? '')),
            ],
            array_filter($this->vehicles(), fn ($v) => ($v['stage'] ?? null) === 'instock')
        ));
    }
}

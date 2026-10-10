<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Legacy REP.customs — arrived/clearing/cleared or customs_no present. */
class CustomsReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'customs';
    }

    public function titleKey(): string
    {
        return 'reports.titles.customs';
    }

    public function columns(): array
    {
        return [
            $this->field('model'),
            $this->field('vin'),
            $this->field('bl'),
            $this->field('stage'),
            $this->field('cust_no'),
            $this->field('cust_date'),
            $this->field('duty'),
        ];
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
                $this->formatDate($v['cust_date'] ?? ''),
                $this->formatMoney($v['duty'] ?? ''),
            ],
            array_filter(
                $this->vehicles(),
                fn ($v) => in_array($v['stage'] ?? null, ['arrived', 'clearing', 'cleared'], true) || ($v['cust_no'] ?? '') !== ''
            )
        ));
    }
}

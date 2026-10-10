<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Legacy REP.stock — instock + sold (not delivered). */
class StockReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'stock';
    }

    public function titleKey(): string
    {
        return 'reports.titles.stock';
    }

    public function columns(): array
    {
        return [
            $this->field('brand'),
            $this->field('model'),
            $this->field('vin'),
            $this->field('stage'),
            $this->field('loc'),
            $this->field('in_date'),
            $this->column('days_in_warehouse'),
            $this->field('client'),
            $this->field('ord_no'),
        ];
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
                $this->formatDate($v['in_date'] ?? ''),
                $this->daysSince($v['in_date'] ?? ($v['arr'] ?? '')),
                $v['client'] ?? '',
                $v['ord_no'] ?? '',
            ],
            array_filter($this->vehicles(), fn ($v) => in_array($v['stage'] ?? null, ['instock', 'sold'], true))
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Profit per vehicle: sale_price - cost - duty. */
class ProfitReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'profit';
    }

    public function titleKey(): string
    {
        return 'reports.titles.profit';
    }

    public function columns(): array
    {
        return [
            $this->field('vin'),
            $this->field('model'),
            $this->field('client'),
            $this->field('cost'),
            $this->field('duty'),
            $this->column('total_cost'),
            $this->field('price'),
            $this->column('profit'),
        ];
    }

    public function rows(): array
    {
        $rows = [];
        foreach ($this->vehicles() as $v) {
            $cost = (float) ($v['cost'] ?? 0);
            $duty = (float) ($v['duty'] ?? 0);
            $price = (float) ($v['price'] ?? 0);
            $total = $cost + $duty;

            $rows[] = [
                $v['vin'] ?? '',
                $v['model'] ?? '',
                $v['client'] ?? '',
                $cost !== 0.0 ? $cost : '',
                $duty !== 0.0 ? $duty : '',
                $total !== 0.0 ? $total : '',
                $price !== 0.0 ? $price : '',
                $price !== 0.0 || $total !== 0.0 ? $price - $total : '',
            ];
        }

        // Sort desc on numeric profit before formatting money to strings.
        usort($rows, fn ($a, $b) => ((float) ($b[7] === '' ? 0 : $b[7])) <=> ((float) ($a[7] === '' ? 0 : $a[7])));

        return array_map(fn ($row) => [
            $row[0],
            $row[1],
            $row[2],
            $this->formatMoney($row[3]),
            $this->formatMoney($row[4]),
            $this->formatMoney($row[5]),
            $this->formatMoney($row[6]),
            $this->formatMoney($row[7]),
        ], $rows);
    }
}

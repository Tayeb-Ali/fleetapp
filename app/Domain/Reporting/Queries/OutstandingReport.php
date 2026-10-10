<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Outstanding balances: sale_price - amount_paid > 0. */
class OutstandingReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'outstanding';
    }

    public function titleKey(): string
    {
        return 'reports.titles.outstanding';
    }

    public function columns(): array
    {
        return [
            $this->field('vin'),
            $this->field('client'),
            $this->field('phone'),
            $this->field('ord_no'),
            $this->field('price'),
            $this->field('paid'),
            $this->column('balance_due'),
        ];
    }

    public function rows(): array
    {
        $rows = [];
        foreach ($this->vehicles() as $v) {
            $price = (float) ($v['price'] ?? 0);
            $paid = (float) ($v['paid'] ?? 0);
            $balance = $price - $paid;

            if ($balance <= 0) {
                continue;
            }

            $rows[] = [
                $v['vin'] ?? '',
                $v['client'] ?? '',
                $v['phone'] ?? '',
                $v['ord_no'] ?? '',
                $price,
                $paid,
                $balance,
            ];
        }

        // Sort desc on numeric balance before formatting money to strings.
        usort($rows, fn ($a, $b) => $b[6] <=> $a[6]);

        return array_map(fn ($row) => [
            $row[0],
            $row[1],
            $row[2],
            $row[3],
            $this->formatMoney($row[4]),
            $this->formatMoney($row[5]),
            $this->formatMoney($row[6]),
        ], $rows);
    }
}

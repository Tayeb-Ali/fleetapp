<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Legacy REP.sales — grouped by buyer_name (client) with base-currency totals. */
class SalesByBuyerReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'sales';
    }

    public function titleKey(): string
    {
        return 'reports.titles.sales';
    }

    public function columns(): array
    {
        return [
            $this->field('client'),
            $this->column('vehicle_count'),
            $this->column('total_sale_price'),
            $this->column('total_paid'),
            $this->column('delivered'),
            $this->column('awaiting_delivery'),
        ];
    }

    public function rows(): array
    {
        $groups = [];
        foreach ($this->vehiclesWithCosts() as $v) {
            if (($v['client'] ?? '') === '') {
                continue;
            }
            $groups[$v['client']][] = $v;
        }

        $rows = [];
        foreach ($groups as $client => $a) {
            $paidBase = 0.0;
            foreach ($a as $v) {
                $paid = (float) ($v['paid'] ?? 0);
                $rate = $v['sale_rate'] ?? null;

                if ($paid > 0 && $rate !== null && (float) $rate > 0) {
                    $paidBase += $paid / (float) $rate;
                }
            }

            $rows[] = [
                $client,
                count($a),
                $this->sum($a, 'sale_base'),
                $paidBase,
                count(array_filter($a, fn ($v) => ($v['stage'] ?? null) === 'delivered')),
                count(array_filter($a, fn ($v) => ($v['stage'] ?? null) === 'sold')),
            ];
        }

        // Sort by vehicle count desc before formatting money to strings.
        usort($rows, fn ($a, $b) => $b[1] <=> $a[1]);

        return array_map(fn ($row) => [
            $row[0],
            $row[1],
            $this->formatMoney($row[2]),
            $this->formatMoney($row[3]),
            $row[4],
            $row[5],
        ], $rows);
    }
}

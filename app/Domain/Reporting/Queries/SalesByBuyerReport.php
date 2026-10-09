<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Legacy REP.sales — grouped by buyer_name (client) with totals. */
class SalesByBuyerReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'sales';
    }

    public function titleKey(): string
    {
        return 'Sales by buyer';
    }

    public function columns(): array
    {
        return ['Buyer / Organization', 'Vehicle count', 'Total sale price', 'Total paid', 'Delivered', 'awaiting delivery'];
    }

    public function rows(): array
    {
        $groups = [];
        foreach ($this->vehicles() as $v) {
            if (($v['client'] ?? '') === '') {
                continue;
            }
            $groups[$v['client']][] = $v;
        }

        $rows = [];
        foreach ($groups as $client => $a) {
            $rows[] = [
                $client,
                count($a),
                $this->sum($a, 'price'),
                $this->sum($a, 'paid'),
                count(array_filter($a, fn ($v) => ($v['stage'] ?? null) === 'delivered')),
                count(array_filter($a, fn ($v) => ($v['stage'] ?? null) === 'sold')),
            ];
        }

        usort($rows, fn ($a, $b) => $b[1] <=> $a[1]);

        return $rows;
    }
}

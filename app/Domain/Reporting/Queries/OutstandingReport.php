<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Outstanding balances: sale_price - amount_paid > 0. */
class OutstandingReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'outstanding';
    }

    public function titleKey(): string
    {
        return 'Outstanding balances';
    }

    public function columns(): array
    {
        return ['Chassis No. (VIN)', 'Buyer / Organization', 'Buyer phone', 'Sale order no.', 'Sale price', 'Amount paid', 'Balance due'];
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

        usort($rows, fn ($a, $b) => $b[6] <=> $a[6]);

        return $rows;
    }
}

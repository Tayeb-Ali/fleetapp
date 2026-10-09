<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Profit per vehicle: sale_price - cost - duty. */
class ProfitReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'profit';
    }

    public function titleKey(): string
    {
        return 'Profit by vehicle';
    }

    public function columns(): array
    {
        return ['Chassis No. (VIN)', 'Model', 'Buyer / Organization', 'Purchase price', 'Customs duty', 'Total cost', 'Sale price', 'Profit'];
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

        usort($rows, fn ($a, $b) => ((float) ($b[7] === '' ? 0 : $b[7])) <=> ((float) ($a[7] === '' ? 0 : $a[7])));

        return $rows;
    }
}

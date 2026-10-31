<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/**
 * Profit per vehicle in the base currency.
 *
 * Total cost = purchase + shipping + landed + modification (FX captured at
 * transaction time), profit = sale base - total cost.
 */
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
            $this->column('purchase_base'),
            $this->column('shipping_base'),
            $this->column('landed_base'),
            $this->column('modification_base'),
            $this->column('total_cost'),
            $this->column('sale_base'),
            $this->column('profit'),
        ];
    }

    public function rows(): array
    {
        $rows = [];
        foreach ($this->vehiclesWithCosts() as $v) {
            $rows[] = [
                $v['vin'] ?? '',
                $v['model'] ?? '',
                $v['client'] ?? '',
                $v['purchase_base'] ?? null,
                $v['shipping_base'] ?? null,
                $v['landed_base'] ?? null,
                $v['modification_base'] ?? null,
                $v['total_cost'] ?? null,
                $v['sale_base'] ?? null,
                $v['profit'] ?? null,
            ];
        }

        // Sort desc on numeric profit before formatting money to strings.
        usort($rows, fn ($a, $b) => ((float) ($b[9] ?? 0)) <=> ((float) ($a[9] ?? 0)));

        return array_map(fn ($row) => [
            $row[0],
            $row[1],
            $row[2],
            $this->formatMoney($row[3]),
            $this->formatMoney($row[4]),
            $this->formatMoney($row[5]),
            $this->formatMoney($row[6]),
            $this->formatMoney($row[7]),
            $this->formatMoney($row[8]),
            $this->formatMoney($row[9]),
        ], $rows);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/**
 * Cost build-up per vehicle: purchase, shipping, landed, modification,
 * total cost, sale and profit — all expressed in the base currency.
 *
 * Native amounts keep the currency the client actually paid so the report
 * can be audited against the proof documents.
 */
class CostingReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'costing';
    }

    public function titleKey(): string
    {
        return 'reports.titles.costing';
    }

    public function columns(): array
    {
        return [
            $this->field('vin'),
            $this->field('model'),
            $this->field('stage'),
            $this->field('pdate'),
            $this->field('ship'),
            $this->field('sale_date'),
            $this->column('purchase_native'),
            $this->column('purchase_base'),
            $this->column('shipping_native'),
            $this->column('shipping_base'),
            $this->column('landed_base'),
            $this->column('modification_base'),
            $this->column('total_cost'),
            $this->column('sale_native'),
            $this->column('sale_base'),
            $this->column('profit'),
        ];
    }

    public function rows(): array
    {
        return array_map(
            fn (array $v) => [
                $v['vin'] ?? '',
                $v['model'] ?? '',
                $this->stageLabel($v['stage'] ?? null),
                $this->formatDate($v['purchased_at'] ?? null),
                $this->formatDate($v['shipped_at'] ?? null),
                $this->formatDate($v['sold_at'] ?? null),
                $this->formatMoneyIn($v['purchase_native'] ?? null, (string) ($v['purchase_currency'] ?? '')),
                $this->formatMoney($v['purchase_base'] ?? null),
                $this->formatMoneyIn($v['shipping_native'] ?? null, (string) ($v['shipping_currency'] ?? '')),
                $this->formatMoney($v['shipping_base'] ?? null),
                $this->formatMoney($v['landed_base'] ?? null),
                $this->formatMoney($v['modification_base'] ?? null),
                $this->formatMoney($v['total_cost'] ?? null),
                $this->formatMoneyIn($v['sale_native'] ?? null, (string) ($v['sale_currency'] ?? '')),
                $this->formatMoney($v['sale_base'] ?? null),
                $this->formatMoney($v['profit'] ?? null),
            ],
            $this->vehiclesWithCosts(),
        );
    }
}

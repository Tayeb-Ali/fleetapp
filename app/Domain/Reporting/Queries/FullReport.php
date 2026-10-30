<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Legacy REP.full (rec:true) — all columns, one row per vehicle. */
class FullReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    /** All vehicle fields except stage (legacy FK = C minus stage). */
    public const FIELDS = [
        'brand', 'model', 'vin', 'fuel', 'mod', 'pdate', 'supplier',
        'cost', 'purchase_currency', 'purchase_fx_rate', 'purchase_base_amount',
        'bl', 'voy', 'pol', 'pod', 'dest', 'ship', 'arr',
        'shipping_cost', 'shipping_currency', 'shipping_fx_rate', 'shipping_base_amount',
        'cust_no', 'cust_date', 'landed_cost', 'modification_cost', 'loc', 'in_date',
        'ord_no', 'client', 'phone', 'price', 'sale_currency', 'sale_fx_rate', 'sale_base_amount',
        'paid', 'sale_date', 'recv', 'del_date', 'remarks',
    ];

    /** Date fields are formatted with the active locale. */
    private const DATE_FIELDS = ['pdate', 'ship', 'arr', 'cust_date', 'in_date', 'sale_date', 'del_date'];

    /** Money fields are formatted with the configured currency. */
    private const MONEY_FIELDS = [
        'cost', 'shipping_cost', 'landed_cost', 'modification_cost',
        'purchase_base_amount', 'shipping_base_amount', 'sale_base_amount',
        'price', 'paid',
    ];

    public function key(): string
    {
        return 'full';
    }

    public function titleKey(): string
    {
        return 'reports.titles.full';
    }

    public function columns(): array
    {
        return array_merge(
            [$this->field('stage')],
            array_map(fn ($k) => $this->field($k), self::FIELDS)
        );
    }

    public function rows(): array
    {
        return array_map(
            fn ($v) => array_merge(
                [$this->stageLabel($v['stage'] ?? null)],
                array_map(fn ($k) => $this->formatCell($v, $k), self::FIELDS)
            ),
            $this->vehicles()
        );
    }

    protected function formatCell(array $v, string $key): mixed
    {
        if (in_array($key, self::MONEY_FIELDS, true)) {
            return $this->formatMoney($this->num($v, $key));
        }

        if (in_array($key, self::DATE_FIELDS, true)) {
            return $this->formatDate((string) ($v[$key] ?? ''));
        }

        return $this->num($v, $key);
    }
}

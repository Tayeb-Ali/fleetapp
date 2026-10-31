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

    /** Native amounts are formatted with the currency they were paid in. */
    private const NATIVE_CURRENCY = [
        'cost' => 'purchase_currency',
        'shipping_cost' => 'shipping_currency',
        'price' => 'sale_currency',
        'paid' => 'sale_currency',
    ];

    /** Base-currency amounts (configured base currency). */
    private const BASE_MONEY_FIELDS = [
        'landed_cost', 'modification_cost',
        'purchase_base_amount', 'shipping_base_amount', 'sale_base_amount',
    ];

    /** Snapshot columns fall back to the computed breakdown when unset. */
    private const COMPUTED_BASE = [
        'purchase_base_amount' => 'purchase_base',
        'shipping_base_amount' => 'shipping_base',
        'sale_base_amount' => 'sale_base',
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
            array_map(fn ($k) => $this->field($k), self::FIELDS),
            [$this->column('total_cost'), $this->column('profit')]
        );
    }

    public function rows(): array
    {
        return array_map(
            fn ($v) => array_merge(
                [$this->stageLabel($v['stage'] ?? null)],
                array_map(fn ($k) => $this->formatCell($v, $k), self::FIELDS),
                [
                    $this->formatMoney($v['total_cost'] ?? null),
                    $this->formatMoney($v['profit'] ?? null),
                ]
            ),
            $this->vehiclesWithCosts()
        );
    }

    protected function formatCell(array $v, string $key): mixed
    {
        if (array_key_exists($key, self::NATIVE_CURRENCY)) {
            return $this->formatMoneyIn(
                $this->num($v, $key),
                (string) ($v[self::NATIVE_CURRENCY[$key]] ?? '') ?: $this->reportCurrency()
            );
        }

        if (in_array($key, self::BASE_MONEY_FIELDS, true)) {
            $value = $this->num($v, $key);

            if (($value === '' || $value === null) && isset(self::COMPUTED_BASE[$key])) {
                $value = $v[self::COMPUTED_BASE[$key]] ?? null;
            }

            return $this->formatMoney($value);
        }

        if (in_array($key, self::DATE_FIELDS, true)) {
            return $this->formatDate((string) ($v[$key] ?? ''));
        }

        return $this->num($v, $key);
    }
}

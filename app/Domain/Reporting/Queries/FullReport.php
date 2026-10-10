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
        'brand', 'model', 'vin', 'fuel', 'mod', 'pdate', 'supplier', 'cost',
        'bl', 'voy', 'pol', 'pod', 'dest', 'ship', 'arr',
        'cust_no', 'cust_date', 'duty', 'loc', 'in_date',
        'ord_no', 'client', 'phone', 'price', 'paid', 'sale_date',
        'recv', 'del_date', 'remarks',
    ];

    /** Date fields are formatted with the active locale. */
    private const DATE_FIELDS = ['pdate', 'ship', 'arr', 'cust_date', 'in_date', 'sale_date', 'del_date'];

    /** Money fields are formatted with the configured currency. */
    private const MONEY_FIELDS = ['cost', 'duty', 'price', 'paid'];

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

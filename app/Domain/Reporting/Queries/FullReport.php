<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Legacy REP.full (rec:true) — all columns, one row per vehicle. */
class FullReport implements ReportQuery
{
    use FetchesVehicles;

    /** All vehicle fields except stage (legacy FK = C minus stage). */
    public const FIELDS = [
        'brand', 'model', 'vin', 'fuel', 'mod', 'pdate', 'supplier', 'cost',
        'bl', 'voy', 'pol', 'pod', 'dest', 'ship', 'arr',
        'cust_no', 'cust_date', 'duty', 'loc', 'in_date',
        'ord_no', 'client', 'phone', 'price', 'paid', 'sale_date',
        'recv', 'del_date', 'remarks',
    ];

    public const LABELS = [
        'brand' => 'Brand',
        'model' => 'Model',
        'vin' => 'Chassis No. (VIN)',
        'fuel' => 'Fuel / Engine',
        'mod' => 'Modified (Yes/No)',
        'pdate' => 'Purchase date',
        'supplier' => 'Supplier / Overseas Showroom',
        'cost' => 'Purchase price',
        'bl' => 'Bill of Lading No. (B/L)',
        'voy' => 'Voyage No.',
        'pol' => 'Port of loading',
        'pod' => 'Port of discharge',
        'dest' => 'Final destination',
        'ship' => 'Shipping date',
        'arr' => 'Arrival date',
        'cust_no' => 'Customs declaration no.',
        'cust_date' => 'Clearance date',
        'duty' => 'Customs duty',
        'loc' => 'Warehouse location',
        'in_date' => 'Warehouse entry date',
        'ord_no' => 'Sale order no.',
        'client' => 'Buyer / Organization',
        'phone' => 'Buyer phone',
        'price' => 'Sale price',
        'paid' => 'Amount paid',
        'sale_date' => 'Sale order date',
        'recv' => 'Receiver name',
        'del_date' => 'Delivery date',
        'remarks' => 'Remarks',
    ];

    public function key(): string
    {
        return 'full';
    }

    public function titleKey(): string
    {
        return 'Full technical report (all vehicle lifecycle data)';
    }

    public function columns(): array
    {
        return array_merge(['Stage'], array_map(fn ($k) => self::LABELS[$k], self::FIELDS));
    }

    public function rows(): array
    {
        return array_map(
            fn ($v) => array_merge(
                [$this->stageLabel($v['stage'] ?? null)],
                array_map(fn ($k) => $this->num($v, $k), self::FIELDS)
            ),
            $this->vehicles()
        );
    }
}

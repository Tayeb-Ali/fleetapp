<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Legacy REP.shipments — grouped by voyage_no (+ ship date). */
class ShipmentsReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'shipments';
    }

    public function titleKey(): string
    {
        return 'Shipments and ports';
    }

    public function columns(): array
    {
        return ['Voyage No.', 'Port of loading', 'Port of discharge', 'Shipping date', 'Arrival date', 'Vehicle count', 'Still in transit', 'Arrived or later'];
    }

    public function rows(): array
    {
        $groups = [];
        foreach ($this->vehicles() as $v) {
            if (($v['voy'] ?? '') === '') {
                continue;
            }
            $groups[($v['voy'] ?? '').'|'.($v['ship'] ?? '')][] = $v;
        }

        $rows = [];
        foreach ($groups as $a) {
            $v = $a[0];
            $rows[] = [
                $v['voy'] ?? '',
                $v['pol'] ?? '',
                $v['pod'] ?? '',
                $v['ship'] ?? '',
                $v['arr'] ?? '',
                count($a),
                count(array_filter($a, fn ($x) => ($x['stage'] ?? null) === 'shipped')),
                count(array_filter($a, fn ($x) => ! in_array($x['stage'] ?? null, ['purchased', 'shipped'], true))),
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) $b[3], (string) $a[3]));

        return $rows;
    }
}

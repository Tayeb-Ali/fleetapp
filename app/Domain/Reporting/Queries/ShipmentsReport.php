<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Legacy REP.shipments — grouped by voyage_no (+ ship date). */
class ShipmentsReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'shipments';
    }

    public function titleKey(): string
    {
        return 'reports.titles.shipments';
    }

    public function columns(): array
    {
        return [
            $this->field('voy'),
            $this->field('pol'),
            $this->field('pod'),
            $this->field('ship'),
            $this->field('arr'),
            $this->column('vehicle_count'),
            $this->column('still_in_transit'),
            $this->column('arrived_or_later'),
        ];
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

        // Sort by raw shipping date desc before formatting it to a label.
        usort($rows, fn ($a, $b) => strcmp((string) $b[3], (string) $a[3]));

        return array_map(fn ($row) => [
            $row[0],
            $row[1],
            $row[2],
            $this->formatDate($row[3]),
            $this->formatDate($row[4]),
            $row[5],
            $row[6],
            $row[7],
        ], $rows);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Legacy REP.quality (lines 81-85) — data quality check. */
class QualityCheckReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'quality';
    }

    public function titleKey(): string
    {
        return 'Data quality check';
    }

    public function columns(): array
    {
        return ['Chassis No. (VIN)', 'Model', 'Stage', 'Issue'];
    }

    public function rows(): array
    {
        $today = date('Y-m-d');
        $pastShipping = ['shipped', 'arrived', 'clearing', 'cleared', 'instock', 'sold', 'delivered'];
        $rows = [];

        foreach ($this->vehicles() as $v) {
            $issues = [];
            $stage = $v['stage'] ?? '';

            if (($v['ship'] ?? '') !== '' && $v['ship'] > $today) {
                $issues[] = 'Shipping date is in the future';
            }
            if (($v['arr'] ?? '') !== '' && $v['arr'] > $today && ! in_array($stage, ['purchased', 'shipped'], true)) {
                $issues[] = 'Arrival date is in the future but the vehicle is past the shipping stage';
            }
            if (($v['ship'] ?? '') !== '' && ($v['arr'] ?? '') !== '' && $v['arr'] < $v['ship']) {
                $issues[] = 'Arrival date is before shipping date';
            }
            if (in_array($stage, $pastShipping, true) && ($v['bl'] ?? '') === '') {
                $issues[] = 'Missing Bill of Lading (B/L) number';
            }
            if (in_array($stage, ['sold', 'delivered'], true) && ($v['client'] ?? '') === '') {
                $issues[] = 'Sold without buyer name';
            }
            if ($stage === 'delivered' && (($v['recv'] ?? '') === '' || ($v['del_date'] ?? '') === '')) {
                $issues[] = 'Delivered without receiver name or delivery date';
            }
            if (in_array($stage, ['instock', 'sold'], true) && ($v['loc'] ?? '') === '') {
                $issues[] = 'In stock without warehouse location';
            }

            foreach ($issues as $issue) {
                $rows[] = [$v['vin'] ?? '', $v['model'] ?? '', $this->stageLabel($stage), $issue];
            }
        }

        return $rows;
    }
}

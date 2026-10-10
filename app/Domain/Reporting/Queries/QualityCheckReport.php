<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Legacy REP.quality (lines 81-85) — data quality check. */
class QualityCheckReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'quality';
    }

    public function titleKey(): string
    {
        return 'reports.titles.quality';
    }

    public function columns(): array
    {
        return [$this->field('vin'), $this->field('model'), $this->field('stage'), $this->column('issue')];
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
                $issues[] = 'shipping_future';
            }
            if (($v['arr'] ?? '') !== '' && $v['arr'] > $today && ! in_array($stage, ['purchased', 'shipped'], true)) {
                $issues[] = 'arrival_future';
            }
            if (($v['ship'] ?? '') !== '' && ($v['arr'] ?? '') !== '' && $v['arr'] < $v['ship']) {
                $issues[] = 'arrival_before_shipping';
            }
            if (in_array($stage, $pastShipping, true) && ($v['bl'] ?? '') === '') {
                $issues[] = 'missing_bl';
            }
            if (in_array($stage, ['sold', 'delivered'], true) && ($v['client'] ?? '') === '') {
                $issues[] = 'sold_without_buyer';
            }
            if ($stage === 'delivered' && (($v['recv'] ?? '') === '' || ($v['del_date'] ?? '') === '')) {
                $issues[] = 'delivered_incomplete';
            }
            if (in_array($stage, ['instock', 'sold'], true) && ($v['loc'] ?? '') === '') {
                $issues[] = 'instock_without_location';
            }
            if (in_array($stage, ['sold', 'delivered'], true) && ((float) ($v['price'] ?? 0)) <= 0) {
                $issues[] = 'sold_without_price';
            }
            if (in_array($stage, ['sold', 'delivered'], true) && ($v['paid'] ?? '') === '') {
                $issues[] = 'sold_without_paid';
            }
            if ((float) ($v['paid'] ?? 0) > (float) ($v['price'] ?? 0) && ((float) ($v['price'] ?? 0)) > 0) {
                $issues[] = 'overpaid';
            }

            foreach ($issues as $code) {
                $rows[] = [$v['vin'] ?? '', $v['model'] ?? '', $this->stageLabel($stage), $this->issueLabel($code)];
            }
        }

        return $rows;
    }
}

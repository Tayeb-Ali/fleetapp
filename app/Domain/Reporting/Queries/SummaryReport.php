<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;

/** Legacy REP.summary — counts per stage + total. */
class SummaryReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    /** @var array<int, string> */
    private const STAGES = ['purchased', 'shipped', 'arrived', 'clearing', 'cleared', 'instock', 'sold', 'delivered'];

    public function key(): string
    {
        return 'summary';
    }

    public function titleKey(): string
    {
        return 'reports.titles.summary';
    }

    public function columns(): array
    {
        return [$this->field('stage'), $this->column('count'), $this->column('share')];
    }

    public function rows(): array
    {
        $vs = $this->vehicles();
        $total = count($vs);
        $rows = [];
        foreach (self::STAGES as $stage) {
            $n = count(array_filter($vs, fn ($v) => ($v['stage'] ?? null) === $stage));
            $rows[] = [$this->stageLabel($stage), $n, $total ? (int) round($n / $total * 100) : 0];
        }
        $rows[] = [$this->column('total'), $total, 100];

        return $rows;
    }
}

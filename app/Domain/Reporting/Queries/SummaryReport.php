<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;

/** Legacy REP.summary — counts per stage + total. */
class SummaryReport implements ReportQuery
{
    use FetchesVehicles;

    public function key(): string
    {
        return 'summary';
    }

    public function titleKey(): string
    {
        return 'Vehicle stage summary';
    }

    public function columns(): array
    {
        return ['Stage', 'Count', 'Share %'];
    }

    public function rows(): array
    {
        $vs = $this->vehicles();
        $total = count($vs);
        $rows = [];
        foreach ($this->stages() as $stage => $label) {
            $n = count(array_filter($vs, fn ($v) => ($v['stage'] ?? null) === $stage));
            $rows[] = [$label, $n, $total ? (int) round($n / $total * 100) : 0];
        }
        $rows[] = ['Total', $total, 100];

        return $rows;
    }
}

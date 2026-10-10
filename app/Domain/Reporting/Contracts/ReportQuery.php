<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Contracts;

/**
 * Single reporting query: metadata + tabular data.
 *
 * Mirrors legacy server.js REP entries (summary, stock, available,
 * sales, shipments, customs, full + quality).
 */
interface ReportQuery
{
    /** Machine key, e.g. "summary" (matches {key} route param). */
    public function key(): string;

    /** Translation key for the title, e.g. "reports.titles.summary". */
    public function titleKey(): string;

    /** Localized report title. */
    public function title(): string;

    /** Localized column headings (legacy REP[].c). */
    public function columns(): array;

    /** Data rows as numeric arrays (legacy REP[].r(vs)). */
    public function rows(): array;
}

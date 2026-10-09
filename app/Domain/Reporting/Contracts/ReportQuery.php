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

    /** Human-readable title (legacy REP[].t). */
    public function titleKey(): string;

    /** Column headings (legacy REP[].c). */
    public function columns(): array;

    /** Data rows as numeric arrays (legacy REP[].r(vs)). */
    public function rows(): array;
}

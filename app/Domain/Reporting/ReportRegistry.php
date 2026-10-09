<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\AvailableReport;
use App\Domain\Reporting\Queries\CustomsReport;
use App\Domain\Reporting\Queries\FullReport;
use App\Domain\Reporting\Queries\QualityCheckReport;
use App\Domain\Reporting\Queries\SalesByBuyerReport;
use App\Domain\Reporting\Queries\ShipmentsReport;
use App\Domain\Reporting\Queries\StockReport;
use App\Domain\Reporting\Queries\SummaryReport;

/** Central registry for all reporting queries. */
class ReportRegistry
{
    /** @return array<string, class-string<ReportQuery>> key => query class */
    public static function map(): array
    {
        return [
            'summary' => SummaryReport::class,
            'stock' => StockReport::class,
            'available' => AvailableReport::class,
            'sales' => SalesByBuyerReport::class,
            'shipments' => ShipmentsReport::class,
            'customs' => CustomsReport::class,
            'full' => FullReport::class,
            'quality' => QualityCheckReport::class,
        ];
    }

    /** @return ReportQuery[] */
    public static function all(): array
    {
        return array_map(fn (string $class) => app($class), self::map());
    }

    public static function find(string $key): ?ReportQuery
    {
        $class = self::map()[$key] ?? null;

        return $class ? app($class) : null;
    }
}

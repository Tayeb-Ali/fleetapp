<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Queries\OutstandingReport;
use App\Domain\Reporting\Queries\ProfitReport;
use App\Domain\Reporting\Queries\QualityCheckReport;
use App\Domain\Reporting\ReportRegistry;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_contains_new_reports(): void
    {
        $map = ReportRegistry::map();

        $this->assertArrayHasKey('profit', $map);
        $this->assertArrayHasKey('outstanding', $map);
        $this->assertSame(10, count($map));
    }

    public function test_profit_report_computes_totals(): void
    {
        Vehicle::factory()->create(['vin' => 'VIN-P1', 'model' => 'HILUX', 'buyer_name' => 'Buyer A', 'cost' => 20000, 'duty' => 1500, 'sale_price' => 30000]);
        Vehicle::factory()->create(['vin' => 'VIN-P2', 'model' => 'RAV4', 'buyer_name' => 'Buyer B', 'cost' => 10000, 'duty' => 500, 'sale_price' => 9000]);

        $report = app(ProfitReport::class);
        $rows = $report->rows();

        $this->assertSame(['Chassis No. (VIN)', 'Model', 'Buyer / Organization', 'Purchase price', 'Customs duty', 'Total cost', 'Sale price', 'Profit'], $report->columns());
        $this->assertCount(2, $rows);

        // Sorted profit desc: +8500 first, -1500 last.
        $this->assertSame('VIN-P1', $rows[0][0]);
        $this->assertSame(8500.0, $rows[0][7]);
        $this->assertSame('VIN-P2', $rows[1][0]);
        $this->assertSame(-1500.0, $rows[1][7]);
    }

    public function test_outstanding_report_only_positive_balances(): void
    {
        Vehicle::factory()->create(['vin' => 'VIN-O1', 'buyer_name' => 'Buyer A', 'sale_order_no' => 'SO-1', 'buyer_phone' => '111', 'sale_price' => 30000, 'amount_paid' => 10000]);
        Vehicle::factory()->create(['vin' => 'VIN-O2', 'buyer_name' => 'Buyer B', 'sale_price' => 5000, 'amount_paid' => 5000]);
        Vehicle::factory()->create(['vin' => 'VIN-O3', 'buyer_name' => 'Buyer C', 'sale_price' => null, 'amount_paid' => null]);

        $rows = app(OutstandingReport::class)->rows();

        $this->assertCount(1, $rows);
        $this->assertSame('VIN-O1', $rows[0][0]);
        $this->assertSame(20000.0, $rows[0][6]);
    }

    public function test_quality_flags_missing_financials(): void
    {
        Vehicle::factory()->create(['vin' => 'VIN-Q1', 'model' => 'HILUX', 'stage' => 'sold', 'buyer_name' => 'Buyer A', 'sale_price' => null, 'amount_paid' => null]);
        Vehicle::factory()->create(['vin' => 'VIN-Q2', 'model' => 'RAV4', 'stage' => 'delivered', 'buyer_name' => 'Buyer B', 'receiver_name' => 'R', 'delivered_at' => '2026-01-02', 'sale_price' => 10000, 'amount_paid' => 12000]);

        $rows = app(QualityCheckReport::class)->rows();
        $byVin = [];
        foreach ($rows as $row) {
            $byVin[$row[0]][] = $row[3];
        }

        $this->assertContains('Sold without sale price', $byVin['VIN-Q1']);
        $this->assertContains('Sold without amount paid', $byVin['VIN-Q1']);
        $this->assertContains('Amount paid exceeds sale price', $byVin['VIN-Q2']);
    }
}

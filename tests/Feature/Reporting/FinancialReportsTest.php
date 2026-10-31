<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Queries\CostingReport;
use App\Domain\Reporting\Queries\FullReport;
use App\Domain\Reporting\Queries\OutstandingReport;
use App\Domain\Reporting\Queries\ProfitReport;
use App\Domain\Reporting\Queries\QualityCheckReport;
use App\Domain\Reporting\Queries\SummaryReport;
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
        $this->assertSame(11, count($map));
    }

    public function test_profit_report_computes_totals(): void
    {
        app()->setLocale('en');
        Vehicle::factory()->create(['vin' => 'VIN-P1', 'model' => 'HILUX', 'buyer_name' => 'Buyer A', 'cost' => 20000, 'duty' => 1500, 'sale_price' => 30000]);
        Vehicle::factory()->create(['vin' => 'VIN-P2', 'model' => 'RAV4', 'buyer_name' => 'Buyer B', 'cost' => 10000, 'duty' => 500, 'sale_price' => 9000]);

        $report = app(ProfitReport::class);
        $rows = $report->rows();

        $this->assertSame([
            'Chassis No. (VIN)', 'Model', 'Buyer / Organization',
            'Purchase in base currency', 'Shipping in base currency', 'Landed cost in base currency',
            'Modification in base currency', 'Total cost', 'Sale in base currency', 'Profit',
        ], $report->columns());
        $this->assertCount(2, $rows);

        // Sorted profit desc: +8500 first, -1500 last (money is formatted).
        $this->assertSame('VIN-P1', $rows[0][0]);
        $this->assertSame('21,500.00 USD', $rows[0][7]);
        $this->assertSame('8,500.00 USD', $rows[0][9]);
        $this->assertSame('VIN-P2', $rows[1][0]);
        $this->assertSame('10,500.00 USD', $rows[1][7]);
        $this->assertSame('-1,500.00 USD', $rows[1][9]);
    }

    public function test_outstanding_report_only_positive_balances(): void
    {
        app()->setLocale('en');
        Vehicle::factory()->create(['vin' => 'VIN-O1', 'buyer_name' => 'Buyer A', 'sale_order_no' => 'SO-1', 'buyer_phone' => '111', 'sale_price' => 30000, 'amount_paid' => 10000]);
        Vehicle::factory()->create(['vin' => 'VIN-O2', 'buyer_name' => 'Buyer B', 'sale_price' => 5000, 'amount_paid' => 5000]);
        Vehicle::factory()->create(['vin' => 'VIN-O3', 'buyer_name' => 'Buyer C', 'sale_price' => null, 'amount_paid' => null]);

        $rows = app(OutstandingReport::class)->rows();

        $this->assertCount(1, $rows);
        $this->assertSame('VIN-O1', $rows[0][0]);
        $this->assertSame('20,000.00 USD', $rows[0][6]);
    }

    public function test_quality_flags_missing_financials(): void
    {
        app()->setLocale('en');
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

    public function test_profit_report_leaves_profit_blank_when_cost_cannot_be_converted(): void
    {
        app()->setLocale('en');
        Vehicle::factory()->create([
            'vin' => 'VIN-P3',
            'cost' => 500,
            'purchase_currency' => 'EUR',
            'purchase_fx_rate' => null,
            'purchase_base_amount' => null,
            'sale_price' => 1000,
            'sale_currency' => 'USD',
            'sale_fx_rate' => 1,
        ]);

        $row = app(ProfitReport::class)->rows()[0];

        $this->assertSame('', $row[7]);
        $this->assertSame('1,000.00 USD', $row[8]);
        $this->assertSame('', $row[9]);
    }

    public function test_full_report_shows_native_currencies_and_computed_total_and_profit(): void
    {
        app()->setLocale('en');
        Vehicle::factory()->create([
            'vin' => 'VIN-FULL-1',
            'stage' => 'instock',
            'cost' => 3672.50,
            'purchase_currency' => 'AED',
            'purchase_fx_rate' => 3.6725,
            'shipping_cost' => 734.50,
            'shipping_currency' => 'AED',
            'shipping_fx_rate' => 3.6725,
            'landed_cost' => 50,
            'mod' => 'Yes',
            'modification_cost' => 25,
            'sale_price' => 2000,
            'sale_currency' => 'USD',
            'sale_fx_rate' => 1,
        ]);

        $report = app(FullReport::class);
        $columns = $report->columns();
        $row = $report->rows()[0];

        $this->assertSame('Total cost', $columns[count($columns) - 2]);
        $this->assertSame('Profit', $columns[count($columns) - 1]);

        $this->assertSame('3,672.50 AED', $row[array_search('Purchase price', $columns, true)]);
        $this->assertSame('1,000.00 USD', $row[array_search('Purchase in base currency', $columns, true)]);
        $this->assertSame('1,275.00 USD', $row[array_search('Total cost', $columns, true)]);
        $this->assertSame('725.00 USD', $row[array_search('Profit', $columns, true)]);
    }

    public function test_costing_report_shows_transaction_dates_and_converted_totals(): void
    {
        app()->setLocale('en');
        Vehicle::factory()->create([
            'vin' => 'VIN-COST-1',
            'stage' => 'sold',
            'purchased_at' => '2026-10-10',
            'shipped_at' => '2026-10-20',
            'sold_at' => '2026-11-05',
            'cost' => 3672.50,
            'purchase_currency' => 'AED',
            'purchase_fx_rate' => 3.6725,
            'shipping_cost' => 734.50,
            'shipping_currency' => 'AED',
            'shipping_fx_rate' => 3.6725,
            'landed_cost' => 50,
            'mod' => 'Yes',
            'modification_cost' => 25,
            'sale_price' => 2000,
            'sale_currency' => 'USD',
            'sale_fx_rate' => 1,
        ]);

        $report = app(CostingReport::class);
        $columns = $report->columns();
        $row = $report->rows()[0];

        $this->assertContains('Purchase date', $columns);
        $this->assertContains('Shipping date', $columns);
        $this->assertContains('Sale order date', $columns);

        $this->assertSame('10 Oct 2026', $row[array_search('Purchase date', $columns, true)]);
        $this->assertSame('20 Oct 2026', $row[array_search('Shipping date', $columns, true)]);
        $this->assertSame('05 Nov 2026', $row[array_search('Sale order date', $columns, true)]);
        $this->assertSame('3,672.50 AED', $row[array_search('Purchase (original currency)', $columns, true)]);
        $this->assertSame('1,275.00 USD', $row[array_search('Total cost', $columns, true)]);
        $this->assertSame('725.00 USD', $row[array_search('Profit', $columns, true)]);
    }

    public function test_report_labels_localize_in_arabic(): void
    {
        app()->setLocale('ar');

        $this->assertSame('ملخص مراحل المركبات', app(SummaryReport::class)->title());
        $this->assertSame([
            'رقم الشاسيه', 'الموديل', 'المشتري / الجهة',
            'الشراء بالعملة الأساسية', 'الشحن بالعملة الأساسية', 'التكلفة الواصلة بالعملة الأساسية',
            'التعديل بالعملة الأساسية', 'إجمالي التكلفة', 'البيع بالعملة الأساسية', 'الربح',
        ], app(ProfitReport::class)->columns());

        Vehicle::factory()->create(['vin' => 'VIN-QA1', 'model' => 'HILUX', 'stage' => 'sold', 'buyer_name' => 'Buyer A', 'sale_price' => null, 'amount_paid' => null]);
        $rows = app(QualityCheckReport::class)->rows();
        $qualities = [];
        foreach ($rows as $row) {
            if ($row[0] === 'VIN-QA1') {
                $qualities[] = $row[3];
            }
        }
        $this->assertContains('مباعة بدون سعر بيع', $qualities);
        $this->assertContains('مباعة بدون مبلغ مدفوع', $qualities);
    }
}

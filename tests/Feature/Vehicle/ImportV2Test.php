<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use App\Domain\Vehicle\Actions\ImportVehiclesFromExcelAction;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportV2Test extends TestCase
{
    use RefreshDatabase;

    protected function makeV2File(array $dataRows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('VTTR');

        // Legend rows 2-7 + header row 9 (mirrors real V2 layout).
        $sheet->fromArray([['RASD Tech']], null, 'A2');
        $header = ['Brand Name', 'Vehicle Type & Model', 'Fuel/Engine Size', 'Modified (Yes/No)', 'Current Location', 'Chassis No.', 'POL', 'POD', 'Voyage Number', 'Final Destination', 'Shipping Date', 'B/L Number', 'Arrival Date', 'Client', 'Remarks', 'Status'];
        $sheet->fromArray([$header], null, 'A9');

        $row = 10;
        foreach ($dataRows as $dataRow) {
            $sheet->fromArray([$dataRow], null, "A{$row}");
            $row++;
        }

        $path = tempnam(sys_get_temp_dir(), 'v2_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_imports_five_statuses_with_serial_dates(): void
    {
        $path = $this->makeV2File([
            ['TOYOTA', 'HILUX 2025', 'Petrol', 'No', 'PZU FZ', 'VIN-NOTSHIPPED-1', 'Jeddah', 'Sawakin', 'JBF151', 'PZU FZ', '', '2879', '', 'Buyer A', 'note', 'Not shipped'],
            ['TOYOTA', 'HILUX 2025', 'Petrol', 'No', '', 'VIN-YET-2', 'Jeddah', 'Sawakin', 'JBF151', 'PZU FZ', 46013, '2879', 46014, '', '', 'Yet to be received'],
            ['TOYOTA', 'LANDCRUISER 2025', 'Petrol 3.5L', 'Yes', 'PZU FZ', 'VIN-READY-3', 'Jeddah', 'Sawakin', 'JBF129', 'PZU FZ', 45758, '2369B', 45759, '', '', 'Ready for Sale'],
            ['TOYOTA', 'HILUX 2025', 'Petrol', 'No', 'Sawakin', 'VIN-SOLD-4', 'Jeddah', 'Sawakin', 'JBF125', 'Sawakin', 45958, '2217C', 45959, 'Ministry Of Health', '', 'Sold'],
            ['TOYOTA', 'HILUX 2025', 'Petrol', 'No', 'Sawakin', 'VIN-DELIVERED-5', 'Jeddah', 'Sawakin', 'JBF125', 'Sawakin', 45958, '2217A', 45959, 'Ministry Of Health', '', 'Sold & Delivered'],
            ['TOYOTA', 'HILUX 2025', 'Petrol', 'No', '', '', 'Jeddah', 'Sawakin', 'JBF151', 'PZU FZ', '', '', '', '', '', 'Not shipped'],
        ]);

        $result = app(ImportVehiclesFromExcelAction::class)->execute($path);

        $this->assertSame(5, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);

        $this->assertSame('purchased', Vehicle::query()->find('VIN-NOTSHIPPED-1')->stageValue());
        $this->assertSame('shipped', Vehicle::query()->find('VIN-YET-2')->stageValue());
        $this->assertSame('instock', Vehicle::query()->find('VIN-READY-3')->stageValue());
        $this->assertSame('sold', Vehicle::query()->find('VIN-SOLD-4')->stageValue());
        $this->assertSame('delivered', Vehicle::query()->find('VIN-DELIVERED-5')->stageValue());

        // Serial dates converted, mod + financial columns preserved (not nulled).
        $ready = Vehicle::query()->find('VIN-READY-3');
        $this->assertSame('Yes', $ready->mod);
        $this->assertSame('2025-04-11', $ready->shipped_at->format('Y-m-d'));
        $this->assertSame('2025-04-12', $ready->arrived_at->format('Y-m-d'));

        unlink($path);
    }

    public function test_sync_is_idempotent_and_never_nulls_financials(): void
    {
        Vehicle::factory()->create([
            'vin' => 'VIN-SYNC-1',
            'stage' => 'sold',
            'cost' => 20000,
            'duty' => 1500,
            'sale_price' => 30000,
            'amount_paid' => 10000,
        ]);

        $path = $this->makeV2File([
            ['TOYOTA', 'HILUX 2025', 'Petrol', 'No', 'Sawakin', 'VIN-SYNC-1', 'Jeddah', 'Sawakin', 'JBF125', 'Sawakin', 45958, '2217C', 45959, 'Ministry Of Health', 'updated remark', 'Sold'],
        ]);

        $result = app(ImportVehiclesFromExcelAction::class)->execute($path);

        $this->assertSame(1, $result['imported']);

        $fresh = Vehicle::query()->find('VIN-SYNC-1');
        $this->assertSame('updated remark', $fresh->remarks);
        $this->assertSame(20000.0, (float) $fresh->cost);
        $this->assertSame(1500.0, (float) $fresh->duty);
        $this->assertSame(30000.0, (float) $fresh->sale_price);
        $this->assertSame(10000.0, (float) $fresh->amount_paid);
        $this->assertSame(20000.0, $fresh->balance);
        $this->assertSame(8500.0, $fresh->profit);

        // Second run changes nothing in count.
        $again = app(ImportVehiclesFromExcelAction::class)->execute($path);
        $this->assertSame(1, $again['imported']);
        $this->assertSame(1, Vehicle::query()->where('vin', 'VIN-SYNC-1')->count());

        unlink($path);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $path = $this->makeV2File([
            ['TOYOTA', 'HILUX 2025', 'Petrol', 'No', '', 'VIN-DRY-1', 'Jeddah', 'Sawakin', 'JBF151', 'PZU FZ', '', '', '', '', '', 'Not shipped'],
            ['TOYOTA', 'HILUX 2025', 'Petrol', 'No', '', '', 'Jeddah', 'Sawakin', 'JBF151', 'PZU FZ', '', '', '', '', '', 'Not shipped'],
        ]);

        $result = app(ImportVehiclesFromExcelAction::class)->execute($path, true);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);
        $this->assertSame(0, Vehicle::query()->count());

        unlink($path);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use App\Domain\Finance\Models\Currency;
use App\Domain\Finance\Services\ExchangeRateService;
use App\Domain\Vehicle\Actions\ImportVehiclesFromExcelAction;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportV3Test extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<int, mixed>>  $dataRows
     */
    protected function makeV3File(array $dataRows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('VTTR');

        $header = [
            'Brand Name', 'Vehicle Type & Model', 'Fuel/Engine Size', 'Purchase Date', 'Purchase Value',
            'Currency', 'In USD', 'Modified (Yes/No)', 'Current Location', 'Chassis No.',
            'POL', 'POD', 'Voyage Number', 'Final Destination', 'B/L Number',
            'Shipping Date', 'Arrival Date', 'Shipping Cost', 'Currency',
            'Landed Cost', 'Modification Cost', 'Total Cost',
            'Client', 'Sales Date', 'Sales Value', 'Currency', 'Remarks', 'Status',
        ];

        $sheet->fromArray([['RASD Tech']], null, 'A2');
        $sheet->fromArray([$header], null, 'A9');

        $row = 10;
        foreach ($dataRows as $dataRow) {
            $sheet->fromArray([$dataRow], null, "A{$row}");
            $row++;
        }

        $path = tempnam(sys_get_temp_dir(), 'v3_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_three_currency_columns_are_mapped_positionally(): void
    {
        app(ExchangeRateService::class)->setRate('AED', '2026-01', 3.6725);
        app(ExchangeRateService::class)->setRate('AED', '2026-02', 3.6725);
        app(ExchangeRateService::class)->setRate('AED', '2026-03', 3.6725);

        $path = $this->makeV3File([
            [
                'TOYOTA', 'HILUX 2025', 'Petrol', '2026-01-10', 3672.5,
                'AED', 1000, 'No', 'Sawakin', 'VIN-V3-1',
                'Jeddah', 'Sawakin', 'JBF151', 'PZU FZ', 'BL-V3-1',
                '2026-02-01', '2026-03-01', 367.25, 'AED',
                50, 0, 1050,
                'Buyer A', '2026-04-01', 7345, 'AED', 'note', 'Sold',
            ],
        ]);

        $result = app(ImportVehiclesFromExcelAction::class)->execute($path);

        $this->assertSame(1, $result['imported']);
        $this->assertSame([], $result['errors']);

        $vehicle = Vehicle::query()->find('VIN-V3-1');

        $this->assertSame('AED', $vehicle->purchase_currency);
        $this->assertSame('AED', $vehicle->shipping_currency);
        $this->assertSame('AED', $vehicle->sale_currency);
        $this->assertEqualsWithDelta(1000.0, (float) $vehicle->purchase_base_amount, 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $vehicle->shipping_base_amount, 0.01);
        $this->assertEqualsWithDelta(50.0, (float) $vehicle->landed_cost, 0.01);
        $this->assertSame('sold', $vehicle->stageValue());

        unlink($path);
    }

    public function test_unknown_currency_is_created_with_a_warning(): void
    {
        $path = $this->makeV3File([
            [
                'NISSAN', 'PATROL', 'Petrol', '2026-01-10', 1000,
                'QAR', 1000, 'No', '', 'VIN-V3-2',
                '', '', '', '', '',
                '', '', '', '',
                '', '', '',
                '', '', '', '', '', 'Not shipped',
            ],
        ]);

        $result = app(ImportVehiclesFromExcelAction::class)->execute($path);

        $this->assertSame(1, $result['imported']);
        $this->assertNotNull(Currency::query()->find('QAR'));
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('QAR', implode(' ', $result['warnings']));

        unlink($path);
    }

    public function test_import_restores_a_soft_deleted_vin(): void
    {
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-V3-3']);
        $vehicle->delete();

        $this->assertSoftDeleted('vehicles', ['vin' => 'VIN-V3-3']);

        $path = $this->makeV3File([
            [
                'TOYOTA', 'RAV4', 'Petrol', '2026-01-10', 1000,
                'USD', 1000, 'No', '', 'VIN-V3-3',
                '', '', '', '', '',
                '', '', '', '',
                '', '', '',
                '', '', '', '', '', 'Not shipped',
            ],
        ]);

        app(ImportVehiclesFromExcelAction::class)->execute($path);

        $this->assertNotSoftDeleted('vehicles', ['vin' => 'VIN-V3-3']);

        unlink($path);
    }

    public function test_modification_cost_is_ignored_when_not_modified(): void
    {
        $path = $this->makeV3File([
            [
                'TOYOTA', 'HILUX', 'Petrol', '2026-01-10', 1000,
                'USD', 1000, 'No', '', 'VIN-V3-4',
                '', '', '', '', '',
                '', '', '', '',
                '', 999, '',
                '', '', '', '', '', 'Not shipped',
            ],
        ]);

        app(ImportVehiclesFromExcelAction::class)->execute($path);

        $vehicle = Vehicle::query()->find('VIN-V3-4');

        $this->assertSame('No', $vehicle->mod);
        $this->assertNull($vehicle->modification_cost);

        unlink($path);
    }
}

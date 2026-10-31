<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Queries\ShipmentsReport;
use App\Domain\Reporting\Support\ReportFilters;
use App\Domain\Vehicle\Models\Vehicle;
use App\Livewire\Reports\ReportShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::factory()->create([
            'username' => 'rf_'.uniqid(),
            'role' => 'admin',
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_date_range_keeps_only_matching_vehicles(): void
    {
        Vehicle::factory()->create([
            'vin' => 'VIN-F-1', 'voyage_no' => 'VOY-JAN', 'shipped_at' => '2026-01-10',
        ]);
        Vehicle::factory()->create([
            'vin' => 'VIN-F-2', 'voyage_no' => 'VOY-FEB', 'shipped_at' => '2026-02-10',
        ]);

        app()->instance(ReportFilters::class, ReportFilters::make(
            'shipments', '2026-01-01', '2026-01-31', 'shipped_at', true,
        ));

        $rows = app(ShipmentsReport::class)->rows();

        $this->assertCount(1, $rows);
        $this->assertSame('VOY-JAN', $rows[0][0]);
    }

    public function test_screen_filters_rows_by_the_chosen_date_field(): void
    {
        $user = $this->makeUser();

        Vehicle::factory()->create([
            'vin' => 'VIN-F-3', 'voyage_no' => 'VOY-JAN', 'shipped_at' => '2026-01-10',
        ]);
        Vehicle::factory()->create([
            'vin' => 'VIN-F-4', 'voyage_no' => 'VOY-FEB', 'shipped_at' => '2026-02-10',
        ]);

        Livewire::actingAs($user)
            ->test(ReportShow::class, ['reportKey' => 'shipments'])
            ->assertSet('dateField', 'shipped_at')
            ->set('from', '2026-01-01')
            ->set('to', '2026-01-31')
            ->assertSee('VOY-JAN')
            ->assertDontSee('VOY-FEB');
    }

    public function test_page_size_slices_rows_and_pagination_moves_between_pages(): void
    {
        $user = $this->makeUser();

        for ($i = 1; $i <= 30; $i++) {
            Vehicle::factory()->create([
                'vin' => sprintf('VIN-P-%02d', $i),
                'model' => sprintf('MODEL-%02d', $i),
                'stage' => 'instock',
            ]);
        }

        Livewire::actingAs($user)
            ->test(ReportShow::class, ['reportKey' => 'available'])
            ->assertSet('perPage', '25')
            ->assertSee('MODEL-25')
            ->assertDontSee('MODEL-26')
            ->assertSee(__('reports.filters.page', ['page' => 1, 'pages' => 2]))
            ->set('perPage', '10')
            ->assertSee('MODEL-01')
            ->assertDontSee('MODEL-11')
            ->call('goto', 2)
            ->assertSee('MODEL-11')
            ->assertDontSee('MODEL-01')
            ->call('goto', 3)
            ->assertSee('MODEL-21')
            ->assertSee('MODEL-30')
            ->assertDontSee('MODEL-20')
            ->assertSee(__('reports.filters.page', ['page' => 3, 'pages' => 3]));
    }

    public function test_filter_panel_toggles_and_keeps_its_state(): void
    {
        $user = $this->makeUser();

        Livewire::actingAs($user)
            ->test(ReportShow::class, ['reportKey' => 'shipments'])
            ->assertSet('filtersOpen', true)
            ->assertSee(__('reports.filters.hide'))
            ->assertSee(__('reports.filters.all_dates'))
            ->call('toggleFilters')
            ->assertSet('filtersOpen', false)
            ->assertSee(__('reports.filters.show'))
            ->assertDontSee(__('reports.filters.all_dates'))
            ->call('toggleFilters')
            ->assertSet('filtersOpen', true)
            ->assertSee(__('reports.filters.hide'));
    }

    public function test_exports_use_the_active_range_in_the_filename(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('reports.export', [
            'key' => 'profit',
            'format' => 'xlsx',
            'from' => '2026-01-01',
            'to' => '2026-01-31',
            'date_field' => 'sold_at',
        ]));

        $response->assertOk();
        $response->assertDownload('profit-2026-01-01_2026-01-31-'.date('Y-m-d').'.xlsx');
    }

    public function test_all_dates_means_no_filter_even_when_the_report_has_a_default_field(): void
    {
        Vehicle::factory()->create([
            'vin' => 'VIN-F-5', 'voyage_no' => 'VOY-MAR', 'shipped_at' => '2026-03-10',
        ]);

        app()->instance(ReportFilters::class, ReportFilters::make(
            'shipments', null, null, '', true,
        ));

        $this->assertCount(1, app(ShipmentsReport::class)->rows());
    }
}

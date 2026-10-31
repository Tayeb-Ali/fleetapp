<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Identity\Services\SettingsService;
use App\Domain\Identity\Support\DateDisplay;
use App\Domain\Reporting\Queries\ShipmentsReport;
use App\Domain\Vehicle\Models\Vehicle;
use App\Livewire\Admin\SettingsEditor;
use App\Livewire\Vehicles\VehicleShow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DateFormatSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::factory()->create([
            'username' => 'dfs_'.uniqid(),
            'role' => 'admin',
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_date_formats_default_to_human_readable_values(): void
    {
        $settings = app(SettingsService::class);

        $this->assertSame(DateDisplay::DEFAULT_SHORT, $settings->getDateFormatShort());
        $this->assertSame(DateDisplay::DEFAULT_FULL, $settings->getDateFormatFull());
        $this->assertSame('2025-11-12', Carbon::parse('2025-11-12')->format('Y-m-d'));
    }

    public function test_admin_can_save_date_formats_and_they_drive_display(): void
    {
        $admin = $this->makeUser();

        Livewire::actingAs($admin)->test(SettingsEditor::class)
            ->set('dateFormatShort', 'd/m/Y')
            ->set('dateFormatFull', 'j F Y')
            ->call('save')
            ->assertHasNoErrors();

        $settings = app(SettingsService::class);

        $this->assertSame('d/m/Y', $settings->getDateFormatShort());
        $this->assertSame('j F Y', $settings->getDateFormatFull());
        $this->assertSame('12/11/2025', DateDisplay::short('2025-11-12'));
        $this->assertSame(
            Carbon::parse('2025-11-12')->translatedFormat('j F Y'),
            DateDisplay::full('2025-11-12')
        );
    }

    public function test_unknown_date_formats_are_rejected_and_never_stored(): void
    {
        $admin = $this->makeUser();

        Livewire::actingAs($admin)->test(SettingsEditor::class)
            ->set('dateFormatShort', 'Y-m-d H:i:s')
            ->call('save')
            ->assertHasErrors(['dateFormatShort']);

        $settings = app(SettingsService::class);
        $settings->update(['date_format_full' => 'not-a-format']);

        $this->assertSame(DateDisplay::DEFAULT_SHORT, $settings->getDateFormatShort());
        $this->assertSame(DateDisplay::DEFAULT_FULL, $settings->getDateFormatFull());
    }

    public function test_vehicle_details_show_readable_dates_not_iso_timestamps(): void
    {
        $admin = $this->makeUser();

        Vehicle::factory()->create([
            'vin' => 'VIN-DATE-1',
            'stage' => 'shipped',
            'shipped_at' => '2025-11-12',
        ]);

        Livewire::actingAs($admin)->test(VehicleShow::class, ['vin' => 'VIN-DATE-1'])
            ->assertSee(Carbon::parse('2025-11-12')->translatedFormat(DateDisplay::DEFAULT_FULL))
            ->assertDontSee('2025-11-12T00:00:00.000000Z');
    }

    public function test_reports_use_the_configured_short_format(): void
    {
        app(SettingsService::class)->update(['date_format_short' => 'd/m/Y']);

        Vehicle::factory()->create([
            'vin' => 'VIN-DATE-2',
            'voyage_no' => 'VOY-DATE',
            'shipped_at' => '2026-01-10',
        ]);

        $rows = app(ShipmentsReport::class)->rows();

        $this->assertCount(1, $rows);
        $this->assertContains('10/01/2026', $rows[0]);
    }
}

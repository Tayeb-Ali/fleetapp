<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneUnshippedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_soft_deletes_old_never_shipped_vehicles(): void
    {
        Vehicle::factory()->create([
            'vin' => 'VIN-OLD-1',
            'stage' => 'purchased',
            'purchased_at' => now()->subMonths(9)->toDateString(),
            'shipped_at' => null,
            'bl' => null,
        ]);

        $this->artisan('fleet:prune-unshipped', ['--force' => true])
            ->assertExitCode(0);

        $this->assertSoftDeleted('vehicles', ['vin' => 'VIN-OLD-1']);
    }

    public function test_it_keeps_recent_shipped_and_sold_vehicles(): void
    {
        Vehicle::factory()->create([
            'vin' => 'VIN-RECENT-1',
            'stage' => 'purchased',
            'purchased_at' => now()->subMonth()->toDateString(),
            'shipped_at' => null,
            'bl' => null,
        ]);

        Vehicle::factory()->create([
            'vin' => 'VIN-SHIPPED-1',
            'stage' => 'shipped',
            'purchased_at' => now()->subMonths(12)->toDateString(),
            'shipped_at' => now()->subMonths(11)->toDateString(),
            'bl' => 'BL-1',
        ]);

        $this->artisan('fleet:prune-unshipped', ['--force' => true])
            ->assertExitCode(0);

        $this->assertNotSoftDeleted('vehicles', ['vin' => 'VIN-RECENT-1']);
        $this->assertNotSoftDeleted('vehicles', ['vin' => 'VIN-SHIPPED-1']);
    }

    public function test_dry_run_lists_without_deleting(): void
    {
        app()->setLocale('en');

        Vehicle::factory()->create([
            'vin' => 'VIN-DRY-OLD',
            'stage' => 'purchased',
            'purchased_at' => now()->subMonths(9)->toDateString(),
            'shipped_at' => null,
            'bl' => null,
        ]);

        $this->artisan('fleet:prune-unshipped', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run')
            ->assertExitCode(0);

        $this->assertNotSoftDeleted('vehicles', ['vin' => 'VIN-DRY-OLD']);
    }

    public function test_months_option_changes_the_cutoff(): void
    {
        Vehicle::factory()->create([
            'vin' => 'VIN-3M-OLD',
            'stage' => 'purchased',
            'purchased_at' => now()->subMonths(4)->toDateString(),
            'shipped_at' => null,
            'bl' => null,
        ]);

        $this->artisan('fleet:prune-unshipped', ['--months' => 12, '--force' => true])
            ->assertExitCode(0);

        $this->assertNotSoftDeleted('vehicles', ['vin' => 'VIN-3M-OLD']);

        $this->artisan('fleet:prune-unshipped', ['--months' => 3, '--force' => true])
            ->assertExitCode(0);

        $this->assertSoftDeleted('vehicles', ['vin' => 'VIN-3M-OLD']);
    }
}

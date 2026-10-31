<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use App\Domain\Finance\Services\ExchangeRateService;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\VehicleCostService;
use App\Livewire\Vehicles\VehicleFinancials;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VehicleFinancialsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'username' => strtolower('vf_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_saving_purchase_amount_captures_currency_rate_and_base(): void
    {
        $admin = $this->makeUser('admin');
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-FIN-1', 'cost' => null]);

        app(ExchangeRateService::class)->setRate('AED', '2026-10', 3.6725);

        Livewire::actingAs($admin)
            ->test(VehicleFinancials::class, ['vin' => $vehicle->vin])
            ->set('form.purchased_at', '2026-10-10')
            ->set('form.cost', '3672.50')
            ->set('form.purchase_currency', 'AED')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $vehicle->fresh();

        $this->assertSame('AED', $fresh->purchase_currency);
        $this->assertEqualsWithDelta(3.6725, (float) $fresh->purchase_fx_rate, 0.000001);
        $this->assertEqualsWithDelta(1000.0, (float) $fresh->purchase_base_amount, 0.01);
    }

    public function test_transaction_rate_overrides_the_monthly_table(): void
    {
        $admin = $this->makeUser('admin');
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-FIN-2', 'cost' => null]);

        app(ExchangeRateService::class)->setRate('AED', '2026-10', 3.6725);

        Livewire::actingAs($admin)
            ->test(VehicleFinancials::class, ['vin' => $vehicle->vin])
            ->set('form.purchased_at', '2026-10-10')
            ->set('form.cost', '1000')
            ->set('form.purchase_currency', 'AED')
            ->set('form.purchase_fx_rate', '4')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $vehicle->fresh();

        $this->assertEqualsWithDelta(4.0, (float) $fresh->purchase_fx_rate, 0.000001);
        $this->assertEqualsWithDelta(250.0, (float) $fresh->purchase_base_amount, 0.01);
    }

    public function test_changing_currency_replaces_the_stored_rate_but_manual_rates_survive_date_changes(): void
    {
        $admin = $this->makeUser('admin');
        $vehicle = Vehicle::factory()->create([
            'vin' => 'VIN-FIN-6',
            'cost' => 100,
            'purchase_currency' => 'USD',
            'purchase_fx_rate' => 1,
        ]);

        app(ExchangeRateService::class)->setRate('AED', '2026-10', 3.6725);

        Livewire::actingAs($admin)
            ->test(VehicleFinancials::class, ['vin' => $vehicle->vin])
            ->set('form.purchased_at', '2026-10-10')
            ->set('form.purchase_currency', 'AED')
            ->assertSet('form.purchase_fx_rate', '3.6725')
            ->set('form.purchase_fx_rate', '4')
            ->set('form.purchased_at', '2026-11-01')
            ->assertSet('form.purchase_fx_rate', '4');
    }

    public function test_modification_cost_is_only_kept_when_modified_is_yes(): void
    {
        $admin = $this->makeUser('admin');
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-FIN-3', 'mod' => 'Yes', 'modification_cost' => 500]);

        Livewire::actingAs($admin)
            ->test(VehicleFinancials::class, ['vin' => $vehicle->vin])
            ->set('form.mod', 'No')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $vehicle->fresh();

        $this->assertSame('No', $fresh->mod);
        $this->assertNull($fresh->modification_cost);
    }

    public function test_profit_uses_purchase_shipping_landed_and_modification(): void
    {
        $vehicle = Vehicle::factory()->create([
            'vin' => 'VIN-FIN-4',
            'cost' => 1000,
            'shipping_cost' => 100,
            'landed_cost' => 50,
            'mod' => 'Yes',
            'modification_cost' => 25,
            'sale_price' => 2000,
        ]);

        $breakdown = app(VehicleCostService::class)->for($vehicle);

        $this->assertEqualsWithDelta(1175.0, $breakdown->totalCost, 0.01);
        $this->assertEqualsWithDelta(825.0, $breakdown->profit, 0.01);
        $this->assertFalse($breakdown->hasMissingRates());
    }

    public function test_qualified_yes_counts_as_modified_for_the_total(): void
    {
        $vehicle = Vehicle::factory()->create([
            'vin' => 'VIN-FIN-9',
            'cost' => 100,
            'mod' => 'Yes - Workshop',
            'modification_cost' => 50,
        ]);

        $breakdown = app(VehicleCostService::class)->for($vehicle);

        $this->assertEqualsWithDelta(150.0, $breakdown->totalCost, 0.01);
    }

    public function test_missing_rate_is_reported_not_invented(): void
    {
        $vehicle = Vehicle::factory()->create([
            'vin' => 'VIN-FIN-5',
            'cost' => 500,
            'purchase_currency' => 'EUR',
            'purchase_fx_rate' => null,
            'purchase_base_amount' => null,
        ]);

        $breakdown = app(VehicleCostService::class)->for($vehicle);

        $this->assertNull($breakdown->purchaseBase);
        $this->assertSame(['purchase'], $breakdown->missingRates);
        $this->assertTrue($breakdown->hasMissingRates());
        $this->assertNull($breakdown->totalCost);
        $this->assertNull($breakdown->profit);
    }

    public function test_total_cost_converts_each_component_with_its_own_currency(): void
    {
        $vehicle = Vehicle::factory()->create([
            'vin' => 'VIN-FIN-7',
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

        $breakdown = app(VehicleCostService::class)->for($vehicle);

        $this->assertEqualsWithDelta(1000.0, $breakdown->purchaseBase, 0.01);
        $this->assertEqualsWithDelta(200.0, $breakdown->shippingBase, 0.01);
        $this->assertEqualsWithDelta(1275.0, $breakdown->totalCost, 0.01);
        $this->assertEqualsWithDelta(725.0, $breakdown->profit, 0.01);
    }

    public function test_saving_a_foreign_amount_without_a_rate_is_rejected(): void
    {
        $admin = $this->makeUser('admin');
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-FIN-8', 'cost' => null]);

        Livewire::actingAs($admin)
            ->test(VehicleFinancials::class, ['vin' => $vehicle->vin])
            ->set('form.purchased_at', '2026-10-10')
            ->set('form.purchase_currency', 'AED')
            ->set('form.purchase_fx_rate', '')
            ->set('form.cost', '3672.50')
            ->call('save')
            ->assertHasErrors(['form.purchase_fx_rate']);

        $this->assertNull($vehicle->fresh()->cost);
    }
}

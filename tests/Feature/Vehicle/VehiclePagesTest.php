<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use App\Domain\Vehicle\Models\Vehicle;
use App\Livewire\Vehicles\RegisterVehicle;
use App\Livewire\Vehicles\VehicleShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VehiclePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'username' => strtolower('vp_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_purchaser_can_view_vehicles_index(): void
    {
        $purchaser = $this->makeUser('purchaser');
        Vehicle::factory()->create(['vin' => 'VP-LIST-001', 'stage' => 'shipped']);
        Vehicle::factory()->create(['vin' => 'VP-LIST-002', 'stage' => 'sold']);

        // Must render rows (stage pill receives Eloquent models with enum stage).
        $this->actingAs($purchaser)->get('/vehicles')
            ->assertOk()
            ->assertSee('VP-LIST-001')
            ->assertSee('VP-LIST-002');
    }

    public function test_purchaser_can_view_create_page(): void
    {
        $purchaser = $this->makeUser('purchaser');

        $this->actingAs($purchaser)->get('/vehicles/create')->assertOk();
    }

    public function test_purchaser_can_register_vehicle(): void
    {
        $purchaser = $this->makeUser('purchaser');
        $vin = 'VP-REG-001';

        Livewire::actingAs($purchaser)->test(RegisterVehicle::class)
            ->set('data.model', 'Hilux')
            ->set('data.vin', $vin)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('vehicles.show', $vin));

        $this->assertDatabaseHas('vehicles', ['vin' => $vin, 'model' => 'Hilux']);
    }

    public function test_register_stores_null_not_empty_string(): void
    {
        $purchaser = $this->makeUser('purchaser');
        $vin = 'VP-EMPTY-001';

        Livewire::actingAs($purchaser)->test(RegisterVehicle::class)
            ->set('data.model', 'Hilux')
            ->set('data.vin', $vin)
            ->call('save')
            ->assertHasNoErrors();

        // Empty optional fields must be null — '' crashes decimal/date casts on read (500 on /reports).
        $this->assertDatabaseHas('vehicles', ['vin' => $vin, 'cost' => null, 'purchased_at' => null]);

        $vehicle = Vehicle::query()->find($vin);
        $this->assertSame([], array_filter($vehicle->toArray(), fn ($v) => $v === ''));
        $this->actingAs($purchaser)->get('/reports')->assertOk();
    }

    public function test_viewer_create_is_denied(): void
    {
        $viewer = $this->makeUser('viewer');
        $purchaser = $this->makeUser('purchaser');

        $this->assertFalse($viewer->can('create', Vehicle::class));
        $this->assertTrue($purchaser->can('create', Vehicle::class));
    }

    public function test_show_page_renders_and_unknown_vin_404s(): void
    {
        $user = $this->makeUser('purchaser');
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($user)->get('/vehicles/'.$vehicle->vin)->assertOk();
        $this->actingAs($user)->get('/vehicles/UNKNOWN-VIN-XYZ')->assertNotFound();
    }

    public function test_admin_can_delete_vehicle(): void
    {
        $admin = $this->makeUser('admin');
        $vehicle = Vehicle::factory()->create();

        Livewire::actingAs($admin)->test(VehicleShow::class, ['vin' => $vehicle->vin])
            ->call('delete')
            ->assertRedirect(route('vehicles.index'));

        $this->assertSoftDeleted('vehicles', ['vin' => $vehicle->vin]);
    }

    public function test_non_admin_cannot_delete_vehicle(): void
    {
        $purchaser = $this->makeUser('purchaser');
        $vehicle = Vehicle::factory()->create();

        Livewire::actingAs($purchaser)->test(VehicleShow::class, ['vin' => $vehicle->vin])
            ->call('delete')
            ->assertNoRedirect();

        $this->assertDatabaseHas('vehicles', ['vin' => $vehicle->vin]);
        $this->assertNotSoftDeleted('vehicles', ['vin' => $vehicle->vin]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Support\TransitionRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemainingTransitionsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_tracker_can_confirm_arrival(): void
    {
        $tracker = $this->makeUser('tracker');

        $vehicle = Vehicle::factory()->shipped()->create();

        $this->assertTrue($tracker->can('transition', [$vehicle, 'arrive']));

        $transition = app(TransitionRegistry::class)->get('arrive');

        $validated = $transition->validateData([
            'arrived_at' => '2026-09-02',
            'pod' => 'Berbera',
        ]);

        $transition->apply($vehicle, $validated, $tracker);

        $this->assertSame('arrived', $vehicle->fresh()->stageValue());
        $this->assertSame('Berbera', $vehicle->fresh()->pod);
    }

    public function test_customs_can_start_clearance(): void
    {
        $customs = $this->makeUser('customs');

        $vehicle = Vehicle::factory()->create(['stage' => 'arrived']);

        $this->assertTrue($customs->can('transition', [$vehicle, 'clear1']));

        $transition = app(TransitionRegistry::class)->get('clear1');

        $validated = $transition->validateData(['customs_no' => 'CUS-1001']);

        $transition->apply($vehicle, $validated, $customs);

        $this->assertSame('clearing', $vehicle->fresh()->stageValue());
        $this->assertSame('CUS-1001', $vehicle->fresh()->customs_no);
    }

    public function test_customs_can_complete_clearance(): void
    {
        $customs = $this->makeUser('customs');

        $vehicle = Vehicle::factory()->create(['stage' => 'clearing', 'customs_no' => 'CUS-1001']);

        $this->assertTrue($customs->can('transition', [$vehicle, 'clear2']));

        $transition = app(TransitionRegistry::class)->get('clear2');

        $validated = $transition->validateData([
            'customs_no' => 'CUS-1001',
            'customs_date' => '2026-09-03',
        ]);

        $transition->apply($vehicle, $validated, $customs);

        $this->assertSame('cleared', $vehicle->fresh()->stageValue());
    }

    public function test_warehouse_can_receive_into_stock(): void
    {
        $warehouse = $this->makeUser('warehouse');

        $vehicle = Vehicle::factory()->create(['stage' => 'cleared']);

        $this->assertTrue($warehouse->can('transition', [$vehicle, 'receive']));

        $transition = app(TransitionRegistry::class)->get('receive');

        $validated = $transition->validateData([
            'warehouse_location' => 'WH-A-1',
            'warehouse_in_at' => '2026-09-04',
        ]);

        $transition->apply($vehicle, $validated, $warehouse);

        $fresh = $vehicle->fresh();
        $this->assertSame('instock', $fresh->stageValue());
        $this->assertSame('WH-A-1', $fresh->warehouse_location);
    }

    public function test_warehouse_can_deliver_sold_vehicle(): void
    {
        $warehouse = $this->makeUser('warehouse');

        $vehicle = Vehicle::factory()->create([
            'stage' => 'sold',
            'sale_order_no' => 'SO-900',
            'buyer_name' => 'Acme Corp',
            'sale_price' => 25000,
            'amount_paid' => 25000,
        ]);

        $this->assertTrue($warehouse->can('transition', [$vehicle, 'deliver']));

        $transition = app(TransitionRegistry::class)->get('deliver');

        $validated = $transition->validateData([
            'receiver_name' => 'Ahmed Ali',
            'delivered_at' => '2026-09-05',
        ]);

        $transition->apply($vehicle, $validated, $warehouse);

        $fresh = $vehicle->fresh();
        $this->assertSame('delivered', $fresh->stageValue());
        $this->assertSame('Ahmed Ali', $fresh->receiver_name);
    }

    public function test_sales_cannot_deliver(): void
    {
        $sales = $this->makeUser('sales');

        $vehicle = Vehicle::factory()->create([
            'stage' => 'sold',
            'sale_order_no' => 'SO-901',
            'buyer_name' => 'Acme Corp',
            'sale_price' => 25000,
            'amount_paid' => 5000,
        ]);

        $this->assertFalse($sales->can('transition', [$vehicle, 'deliver']));
        $this->assertFalse($sales->can('transition', [$vehicle->fresh(), 'deliver']));
    }
}

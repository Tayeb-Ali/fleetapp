<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Support\TransitionRegistry;
use App\Livewire\Vehicles\TransitionForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_admin_can_ship_purchased_to_shipped(): void
    {
        $admin = $this->makeUser('admin');

        $vehicle = Vehicle::factory()->create(['stage' => 'purchased']);

        $this->assertTrue($admin->can('transition', [$vehicle, 'ship']));

        $registry = app(TransitionRegistry::class);
        $transition = $registry->get('ship');

        $validated = $transition->validateData([
            'bl' => 'BL-123456',
            'shipped_at' => '2026-09-01',
            'voyage_no' => 'VOY-1',
        ]);

        $transition->apply($vehicle, $validated, $admin);

        $this->assertSame('shipped', $vehicle->fresh()->stageValue());
        $this->assertSame('BL-123456', $vehicle->fresh()->bl);
    }

    public function test_tracker_cannot_sell(): void
    {
        $tracker = $this->makeUser('tracker');

        $vehicle = Vehicle::factory()->inStock()->create();

        $this->assertFalse($tracker->can('transition', [$vehicle, 'sell']));
        $this->assertFalse($tracker->can('transition', [$vehicle->fresh(), 'sell']));
    }

    public function test_ship_validation_requires_bl(): void
    {
        $this->expectException(ValidationException::class);

        app(TransitionRegistry::class)->get('ship')->validateData([
            'shipped_at' => '2026-09-01',
        ]);
    }

    public function test_ship_validation_requires_shipped_at(): void
    {
        $this->expectException(ValidationException::class);

        app(TransitionRegistry::class)->get('ship')->validateData([
            'bl' => 'BL-123456',
        ]);
    }

    public function test_sell_requires_order_fields(): void
    {
        $full = [
            'sale_order_no' => 'SO-001',
            'buyer_name' => 'Acme Corp',
            'sale_price' => 25000,
            'amount_paid' => 5000,
        ];

        foreach (['sale_order_no', 'buyer_name', 'sale_price', 'amount_paid'] as $missing) {
            $payload = $full;
            unset($payload[$missing]);

            try {
                app(TransitionRegistry::class)->get('sell')->validateData($payload);
                $this->fail("Expected ValidationException when [{$missing}] is missing.");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $this->assertNotEmpty($full);
    }

    public function test_sales_can_issue_sale_order(): void
    {
        $sales = $this->makeUser('sales');

        $vehicle = Vehicle::factory()->inStock()->create();

        $this->assertTrue($sales->can('transition', [$vehicle, 'sell']));

        $transition = app(TransitionRegistry::class)->get('sell');

        $validated = $transition->validateData([
            'sale_order_no' => 'SO-001',
            'buyer_name' => 'Acme Corp',
            'sale_price' => 25000,
            'amount_paid' => 5000,
        ]);

        $transition->apply($vehicle, $validated, $sales);

        $fresh = $vehicle->fresh();
        $this->assertSame('sold', $fresh->stageValue());
        $this->assertSame(20000.0, $fresh->balance);
    }

    public function test_transition_denied_when_stage_mismatch(): void
    {
        $admin = $this->makeUser('admin');

        $vehicle = Vehicle::factory()->create(['stage' => 'purchased']);

        // sell requires instock; purchased vehicle must be denied even for admin.
        $this->assertFalse($admin->can('transition', [$vehicle, 'sell']));
    }

    public function test_transition_form_rejects_a_vehicle_in_the_wrong_stage(): void
    {
        $sales = $this->makeUser('sales');
        $vehicle = Vehicle::factory()->create(['stage' => 'purchased']);

        Livewire::actingAs($sales)
            ->test(TransitionForm::class, ['vin' => $vehicle->vin, 'transitionKey' => 'sell'])
            ->set('data.ord_no', 'SO-EARLY')
            ->set('data.client', 'Acme')
            ->set('data.price', '1000')
            ->set('data.paid', '0')
            ->call('save')
            ->assertHasErrors(['data']);

        $this->assertSame('purchased', $vehicle->fresh()->stageValue());
    }

    public function test_transition_form_requires_a_rate_for_a_foreign_amount(): void
    {
        $purchaser = $this->makeUser('purchaser');
        $vehicle = Vehicle::factory()->create(['stage' => 'purchased']);

        Livewire::actingAs($purchaser)
            ->test(TransitionForm::class, ['vin' => $vehicle->vin, 'transitionKey' => 'ship'])
            ->set('data.bl', 'BL-1')
            ->set('data.ship', '2026-10-10')
            ->set('data.shipping_currency', 'AED')
            ->set('data.shipping_cost', '100')
            ->call('save')
            ->assertHasErrors(['data.shipping_fx_rate']);

        $fresh = $vehicle->fresh();
        $this->assertSame('purchased', $fresh->stageValue());
        $this->assertNull($fresh->shipping_base_amount);
    }

    public function test_transition_form_rejects_a_duplicate_sale_order_number(): void
    {
        $sales = $this->makeUser('sales');
        Vehicle::factory()->create(['stage' => 'sold', 'sale_order_no' => 'SO-DUP']);
        $vehicle = Vehicle::factory()->inStock()->create();

        Livewire::actingAs($sales)
            ->test(TransitionForm::class, ['vin' => $vehicle->vin, 'transitionKey' => 'sell'])
            ->set('data.ord_no', 'SO-DUP')
            ->set('data.client', 'Acme')
            ->set('data.price', '1000')
            ->set('data.paid', '0')
            ->call('save')
            ->assertHasErrors(['data.ord_no']);

        $this->assertSame('instock', $vehicle->fresh()->stageValue());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicle;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Support\TransitionRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
}

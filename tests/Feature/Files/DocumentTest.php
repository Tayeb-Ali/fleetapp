<?php

declare(strict_types=1);

namespace Tests\Feature\Files;

use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role = 'viewer'): User
    {
        return User::factory()->create([
            'username' => strtolower('dc_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_sale_order_without_order_number_returns_400(): void
    {
        $user = $this->makeUser();
        $vehicle = Vehicle::factory()->create(['sale_order_no' => null]);

        $this->actingAs($user)
            ->get(route('documents.sale-order', $vehicle->vin))
            ->assertStatus(400);
    }

    public function test_sale_order_with_order_number_downloads_pdf(): void
    {
        $user = $this->makeUser();
        $vehicle = Vehicle::factory()->create([
            'sale_order_no' => 'SO-101',
            'buyer_name' => 'Acme Corp',
            'sale_price' => 25000,
            'amount_paid' => 5000,
        ]);

        $response = $this->actingAs($user)->get(route('documents.sale-order', $vehicle->vin));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_delivery_note_unless_delivered_returns_400(): void
    {
        $user = $this->makeUser();
        $vehicle = Vehicle::factory()->create(['stage' => 'sold']);

        $this->actingAs($user)
            ->get(route('documents.delivery-note', $vehicle->vin))
            ->assertStatus(400);
    }

    public function test_delivery_note_when_delivered_downloads_pdf(): void
    {
        $user = $this->makeUser();
        $vehicle = Vehicle::factory()->create([
            'stage' => 'delivered',
            'sale_order_no' => 'SO-102',
            'receiver_name' => 'Ahmed Ali',
            'delivered_at' => '2026-09-05',
        ]);

        $response = $this->actingAs($user)->get(route('documents.delivery-note', $vehicle->vin));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('documents.sale-order', 'ANY-VIN'))->assertRedirectToRoute('login');
        $this->get(route('documents.delivery-note', 'ANY-VIN'))->assertRedirectToRoute('login');
    }
}

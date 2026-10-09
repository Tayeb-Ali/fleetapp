<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $brands = ['Toyota', 'Nissan', 'Mitsubishi', 'Honda', 'Mazda', 'Suzuki', 'Hyundai', 'Kia'];
        $brand = $this->faker->randomElement($brands);

        $models = [
            'Toyota' => ['Land Cruiser', 'Hilux', 'RAV4', 'Corolla Cross', 'Hiace'],
            'Nissan' => ['Patrol', 'Navara', 'X-Trail', 'Urvan'],
            'Mitsubishi' => ['Pajero', 'L200', 'Outlander'],
            'Honda' => ['CR-V', 'Accord', 'Civic'],
            'Mazda' => ['CX-5', 'CX-9', 'BT-50'],
            'Suzuki' => ['Jimny', 'Vitara', 'Swift'],
            'Hyundai' => ['Tucson', 'Santa Fe', 'H-1'],
            'Kia' => ['Sportage', 'Sorento', 'Carnival'],
        ];

        return [
            'vin' => strtoupper(Str::random(8).$this->faker->unique()->randomNumber(8, true).Str::random(1)),
            'brand' => $brand,
            'model' => $this->faker->randomElement($models[$brand] ?? ['Model X']),
            'fuel' => $this->faker->randomElement(['Petrol 2.7L', 'Diesel 2.8L', 'Hybrid 2.5L', 'Petrol 1.6L', 'Diesel 3.0L']),
            'mod' => $this->faker->randomElement(['No', 'Yes']),
            'purchased_at' => $this->faker->optional(0.9)->date(),
            'supplier' => $this->faker->optional(0.7)->company(),
            'cost' => $this->faker->optional(0.9)->randomFloat(2, 8000, 65000),
            'bl' => null,
            'voyage_no' => null,
            'pol' => null,
            'pod' => null,
            'destination' => null,
            'shipped_at' => null,
            'arrived_at' => null,
            'customs_no' => null,
            'customs_date' => null,
            'duty' => null,
            'warehouse_location' => null,
            'warehouse_in_at' => null,
            'sale_order_no' => null,
            'buyer_name' => null,
            'buyer_phone' => null,
            'sale_price' => null,
            'amount_paid' => null,
            'sold_at' => null,
            'receiver_name' => null,
            'delivered_at' => null,
            'remarks' => $this->faker->optional(0.3)->sentence(),
            'stage' => VehicleStage::Purchased->value,
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn () => [
            'bl' => 'BL-'.$this->faker->unique()->bothify('######'),
            'voyage_no' => 'VOY-'.$this->faker->bothify('####'),
            'pol' => $this->faker->randomElement(['Jebel Ali', 'Mombasa', 'Djibouti', 'Berbera']),
            'pod' => $this->faker->randomElement(['Berbera', 'Bosaso', 'Mogadishu']),
            'shipped_at' => $this->faker->date(),
            'stage' => VehicleStage::Shipped->value,
        ]);
    }

    public function inStock(): static
    {
        return $this->state(fn () => [
            'bl' => 'BL-'.$this->faker->unique()->bothify('######'),
            'voyage_no' => 'VOY-'.$this->faker->bothify('####'),
            'customs_no' => 'CUS-'.$this->faker->bothify('######'),
            'customs_date' => $this->faker->date(),
            'warehouse_location' => 'WH-'.$this->faker->randomElement(['A', 'B', 'C']).'-'.$this->faker->numberBetween(1, 50),
            'warehouse_in_at' => $this->faker->date(),
            'stage' => VehicleStage::Instock->value,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\VehiclesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiclesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_snapshot_is_seeded_idempotently(): void
    {
        /** @var array{rows: list<array<string, mixed>>} $snapshot */
        $snapshot = json_decode(
            (string) file_get_contents(database_path('seeders/data/vehicles-import.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->seed(VehiclesSeeder::class);
        $this->seed(VehiclesSeeder::class);

        $this->assertDatabaseCount('vehicles', count($snapshot['rows']));
        $this->assertDatabaseHas('vehicles', [
            'vin' => 'JTMAB7BJ8S4201248',
            'brand' => 'TOYOTA',
            'stage' => 'sold',
        ]);
    }
}

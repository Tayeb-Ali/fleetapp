<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/** Timeline of vehicle_activities for one VIN. */
class VehicleHistory extends Component
{
    public string $vin = '';

    public function mount(string $vin): void
    {
        $this->vin = $vin;
    }

    public function render()
    {
        $items = collect();
        try {
            if (Schema::hasTable('vehicle_activities')) {
                $items = DB::table('vehicle_activities')
                    ->where('vin', $this->vin)
                    ->orderByDesc('id')
                    ->limit(200)
                    ->get();
            }
        } catch (\Throwable) {
            $items = collect();
        }

        return view('livewire.vehicles.vehicle-history', [
            'items' => $items,
        ]);
    }
}

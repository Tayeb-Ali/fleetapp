<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\VehicleMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/** Details + history + attachments placeholders (defensive when tables/columns differ). */
class VehicleShow extends Component
{
    public string $vin = '';

    public function mount(string $vin): void
    {
        $this->vin = $vin;
    }

    protected function vehicle(): ?array
    {
        try {
            if (class_exists(Vehicle::class) && Schema::hasTable('vehicles')) {
                $found = Vehicle::query()->where('vin', $this->vin)->first();

                return $found ? $found->toArray() : null;
            }
            if (Schema::hasTable('vehicles')) {
                $row = DB::table('vehicles')->where('vin', $this->vin)->first();

                return $row ? (array) $row : null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    protected function events(): array
    {
        try {
            foreach (['events', 'vehicle_events', 'audits'] as $table) {
                if (Schema::hasTable($table)) {
                    $hasVin = Schema::hasColumn($table, 'vin');
                    if (! $hasVin) {
                        continue;
                    }

                    return DB::table($table)->where('vin', $this->vin)->orderBy('id')->limit(200)->get()
                        ->map(fn ($r) => (array) $r)->all();
                }
            }
        } catch (\Throwable) {
            return [];
        }

        return [];
    }

    protected function files(): array
    {
        try {
            foreach (['files', 'vehicle_files', 'attachments'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'vin')) {
                    return DB::table($table)->where('vin', $this->vin)->orderByDesc('id')->limit(100)->get()
                        ->map(fn ($r) => (array) $r)->all();
                }
            }
        } catch (\Throwable) {
            return [];
        }

        return [];
    }

    public function delete(): mixed
    {
        if ((auth()->user()->role ?? '') !== 'admin') {
            $this->dispatch('toast', __('actions.delete').' — 403');

            return null;
        }

        try {
            if (class_exists(Vehicle::class) && Schema::hasTable('vehicles')) {
                Vehicle::query()->where('vin', $this->vin)->delete();
            } elseif (Schema::hasTable('vehicles')) {
                DB::table('vehicles')->where('vin', $this->vin)->delete();
            }
        } catch (\Throwable) {
            // best effort
        }

        return redirect()->route('vehicles.index');
    }

    public function render()
    {
        $vehicle = $this->vehicle();
        abort_if(! $vehicle, 404, 'Vehicle not found');

        $transitions = VehicleMeta::transitionsFor((string) ($vehicle['stage'] ?? ''));
        $fields = VehicleMeta::fields();

        return view('livewire.vehicles.vehicle-show', [
            'v' => $vehicle,
            'events' => $this->events(),
            'files' => $this->files(),
            'transitions' => $transitions,
            'fieldLabels' => $fields,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/** Create form (purchase registration). */
class RegisterVehicle extends Component
{
    public array $data = [];

    public function mount(): void
    {
        $fields = config('fleet.create_fields', ['brand', 'model', 'vin', 'fuel', 'pdate', 'supplier', 'cost', 'bl', 'voy', 'pol', 'pod', 'ship', 'arr', 'remarks']);
        $this->data = array_fill_keys($fields, '');
    }

    public function save(): mixed
    {
        $required = config('fleet.create_required', ['model', 'vin']);

        $this->validate([
            'data.model' => in_array('model', $required, true) ? 'required|string|max:300' : 'nullable|string|max:300',
            'data.vin' => in_array('vin', $required, true) ? 'required|string|max:300' : 'nullable|string|max:300',
        ]);

        $vin = trim((string) ($this->data['vin'] ?? ''));
        if ($vin === '') {
            $this->addError('data.vin', __('actions.required').': '.__('vehicles.vin'));

            return null;
        }

        $payload = [];
        foreach ($this->data as $k => $v) {
            $payload[$k] = trim((string) $v);
        }
        $payload['stage'] = ! empty($payload['bl']) ? 'shipped' : 'purchased';

        try {
            if (! Schema::hasTable('vehicles')) {
                $this->addError('data.vin', 'vehicles table missing');

                return null;
            }

            // Translate legacy short keys (pdate, voy, ship, ...) to the
            // canonical Vehicle columns, then keep only columns that exist.
            if (class_exists(Vehicle::class)) {
                $payload = Vehicle::translateLegacyKeys($payload);
            }
            $columns = Schema::getColumnListing('vehicles');
            $payload = array_filter($payload, fn ($k) => in_array($k, $columns, true), ARRAY_FILTER_USE_KEY);

            if (class_exists(Vehicle::class)) {
                $model = Vehicle::class;
                if ($model::query()->where('vin', $vin)->exists()) {
                    $this->addError('data.vin', 'Chassis number already registered');

                    return null;
                }
                $fillable = (new $model)->getFillable();
                if ($fillable !== []) {
                    $payload = array_filter($payload, fn ($k) => in_array($k, $fillable, true), ARRAY_FILTER_USE_KEY);
                    $payload['vin'] = $vin;
                }
                $model::query()->create($payload);
            } else {
                if (DB::table('vehicles')->where('vin', $vin)->exists()) {
                    $this->addError('data.vin', 'Chassis number already registered');

                    return null;
                }
                DB::table('vehicles')->insert($payload + ['vin' => $vin]);
            }
        } catch (\Throwable $e) {
            $this->addError('data.vin', $e->getMessage());

            return null;
        }

        return redirect()->route('vehicles.show', $vin);
    }

    public function render()
    {
        return view('livewire.vehicles.register-vehicle', [
            'fields' => config('fleet.create_fields', array_keys($this->data)),
            'required' => config('fleet.create_required', ['model', 'vin']),
            'labels' => config('fleet.fields', []),
        ]);
    }
}

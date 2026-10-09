<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\VehicleMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/** Dynamic transition form — fields come from registry with defensive fallback. */
class TransitionForm extends Component
{
    public string $vin = '';

    public string $transitionKey = '';

    public array $data = [];

    public function mount(string $vin, string $transitionKey): void
    {
        $this->vin = $vin;
        $this->transitionKey = $transitionKey;

        $def = VehicleMeta::transitions()[$transitionKey] ?? null;
        $fields = $def['fields'] ?? [];

        // Defensive fallback when registry is missing the key.
        if ($fields === []) {
            $fields = ['remarks'];
        }

        $this->data = array_fill_keys($fields, '');

        // Prefill with current vehicle values where available.
        try {
            $vehicle = null;
            if (class_exists(Vehicle::class) && Schema::hasTable('vehicles')) {
                $vehicle = Vehicle::query()->where('vin', $vin)->first()?->toArray();
            } elseif (Schema::hasTable('vehicles')) {
                $vehicle = (array) DB::table('vehicles')->where('vin', $vin)->first();
            }
            if (is_array($vehicle)) {
                foreach ($fields as $f) {
                    if (isset($vehicle[$f]) && $vehicle[$f] !== '') {
                        $this->data[$f] = (string) $vehicle[$f];
                    } elseif (($def['fields'] ?? []) !== [] && in_array($f, ['ship', 'arr', 'cust_date', 'in_date', 'sale_date', 'del_date'], true) && ($this->data[$f] === '')) {
                        $this->data[$f] = date('Y-m-d');
                    }
                }
            }
        } catch (\Throwable) {
            // keep defaults
        }
    }

    public function save(): mixed
    {
        $def = VehicleMeta::transitions()[$this->transitionKey] ?? null;
        if (! $def) {
            $this->addError('data', 'Unknown transition');

            return null;
        }

        foreach ((array) ($def['required'] ?? []) as $req) {
            if (trim((string) ($this->data[$req] ?? '')) === '') {
                $this->addError('data.'.$req, __('actions.required').': '.__('vehicles.'.$req));

                return null;
            }
        }

        // Role gate: admin always allowed.
        $role = auth()->user()->role ?? 'viewer';
        if ($role !== 'admin' && ! in_array($role, (array) ($def['roles'] ?? []), true)) {
            $this->addError('data', '403');

            return null;
        }

        try {
            if (! Schema::hasTable('vehicles')) {
                $this->addError('data', 'vehicles table missing');

                return null;
            }

            $columns = Schema::getColumnListing('vehicles');
            $payload = [];
            foreach ((array) ($def['fields'] ?? []) as $f) {
                // Translate legacy short keys to canonical Vehicle columns.
                $col = $f;
                if (class_exists(Vehicle::class)) {
                    $map = Vehicle::LEGACY_MAP;
                    $col = $map[$f] ?? $f;
                }
                if (in_array($col, $columns, true)) {
                    $payload[$col] = trim((string) ($this->data[$f] ?? ''));
                }
            }
            if (in_array('stage', $columns, true)) {
                $payload['stage'] = $def['to'] ?? null;
            }

            if (class_exists(Vehicle::class)) {
                $model = Vehicle::class;
                $fillable = (new $model)->getFillable();
                if ($fillable !== []) {
                    $payload = array_filter($payload, fn ($k) => in_array($k, $fillable, true), ARRAY_FILTER_USE_KEY);
                }
                $model::query()->where('vin', $this->vin)->update($payload);
            } else {
                DB::table('vehicles')->where('vin', $this->vin)->update($payload);
            }

            // Best-effort audit event.
            try {
                if (Schema::hasTable('events') && Schema::hasColumn('events', 'vin')) {
                    DB::table('events')->insert([
                        'ts' => now()->toIso8601String(),
                        'vin' => $this->vin,
                        'uname' => auth()->user()->name ?? 'system',
                        'role' => $role,
                        'action' => $def['label'] ?? $this->transitionKey,
                        'note' => '',
                    ]);
                }
            } catch (\Throwable) {
                // ignore
            }
        } catch (\Throwable $e) {
            $this->addError('data', $e->getMessage());

            return null;
        }

        $this->dispatch('toast', __('app.saved'));

        return redirect()->route('vehicles.show', $this->vin);
    }

    public function render()
    {
        $def = VehicleMeta::transitions()[$this->transitionKey] ?? null;
        abort_if(! $def, 400, 'This action is not available at the current stage');

        return view('livewire.vehicles.transition-form', [
            'def' => $def,
            'labels' => VehicleMeta::fields(),
        ]);
    }
}

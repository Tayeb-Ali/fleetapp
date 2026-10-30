<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Finance\Services\ExchangeRateService;
use App\Domain\Vehicle\Actions\AttachFileAction;
use App\Domain\Vehicle\Enums\AttachmentCategory;
use App\Domain\Vehicle\Events\VehicleTransitioned;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\FinancialFieldsResolver;
use App\Domain\Vehicle\VehicleMeta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Dynamic transition form — fields come from registry with defensive fallback. */
class TransitionForm extends Component
{
    use WithFileUploads;

    public string $vin = '';

    public string $transitionKey = '';

    public array $data = [];

    /** @var array<int, mixed> */
    public array $proofs = [];

    /**
     * Rates the component filled in itself, keyed by data field.
     *
     * @var array<string, string>
     */
    protected array $autoRates = [];

    /** @var array<string, string> */
    protected array $messages = [];

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

        $base = app(CurrencyService::class)->base();
        foreach (['shipping_currency', 'sale_currency'] as $currencyField) {
            if (array_key_exists($currencyField, $this->data)) {
                $this->data[$currencyField] = $base;
            }
        }

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

        $this->prefillRate('shipping');
        $this->prefillRate('sale');
    }

    public function updated(string $name, mixed $value = null): void
    {
        if ($name === 'data.shipping_currency') {
            $this->prefillRate('shipping', true);
        } elseif ($name === 'data.ship') {
            $this->prefillRate('shipping');
        }

        if ($name === 'data.sale_currency') {
            $this->prefillRate('sale', true);
        } elseif ($name === 'data.sale_date') {
            $this->prefillRate('sale');
        }
    }

    public function save(): mixed
    {
        $def = VehicleMeta::transitions()[$this->transitionKey] ?? null;
        if (! $def) {
            $this->addError('data', __('vehicles.unknown_transition'));

            return null;
        }

        foreach ((array) ($def['required'] ?? []) as $req) {
            if (trim((string) ($this->data[$req] ?? '')) === '') {
                $this->addError('data.'.$req, __('actions.required').': '.__('vehicles.'.$req));

                return null;
            }
        }

        if ($this->proofs !== []) {
            $this->validate([
                'proofs' => 'array|max:5',
                'proofs.*' => 'file|max:6144|mimes:pdf,png,jpg,jpeg,webp',
            ]);
        }

        // Role gate: admin always allowed.
        $role = auth()->user()->role ?? 'viewer';
        if ($role !== 'admin' && ! in_array($role, (array) ($def['roles'] ?? []), true)) {
            $this->addError('data', '403');

            return null;
        }

        $oldStage = null;

        try {
            if (! Schema::hasTable('vehicles')) {
                $this->addError('data', __('vehicles.table_missing'));

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
                    // Empty strings crash decimal/date casts on read — store null.
                    $clean = trim((string) ($this->data[$f] ?? ''));
                    $payload[$col] = $clean === '' ? null : $clean;
                }
            }
            if (in_array('stage', $columns, true)) {
                $payload['stage'] = $def['to'] ?? null;
            }

            if (class_exists(Vehicle::class)) {
                $model = Vehicle::class;
                $current = $model::query()->where('vin', $this->vin)->first();
                $currentStage = $current?->stage;
                $oldStage = $currentStage instanceof \BackedEnum ? $currentStage->value : (string) $currentStage;

                // Currency / FX rate / converted base amounts.
                $payload = app(FinancialFieldsResolver::class)->apply(
                    $payload,
                    $current ? $current->toArray() : [],
                );

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
            report($e);
            $this->addError('data', __('vehicles.table_missing'));

            return null;
        }

        $this->attachProofs();

        $this->dispatchTransitioned($oldStage, (string) ($def['to'] ?? ''));

        $this->dispatch('toast', __('app.saved'));

        return redirect()->route('vehicles.show', $this->vin);
    }

    protected function attachProofs(): void
    {
        if ($this->proofs === []) {
            return;
        }

        $category = match ($this->transitionKey) {
            'ship' => AttachmentCategory::Shipping,
            'clear2' => AttachmentCategory::Landed,
            'sell' => AttachmentCategory::Sale,
            default => AttachmentCategory::General,
        };

        $action = app(AttachFileAction::class);

        foreach ($this->proofs as $file) {
            try {
                $action->execute($this->vin, $file, auth()->id(), $category);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    protected function prefillRate(string $prefix, bool $force = false): void
    {
        $map = [
            'shipping' => ['shipping_currency', 'ship', 'shipping_fx_rate'],
            'sale' => ['sale_currency', 'sale_date', 'sale_fx_rate'],
        ];

        if (! isset($map[$prefix])) {
            return;
        }

        [$currencyKey, $dateKey, $rateKey] = $map[$prefix];

        if (! array_key_exists($currencyKey, $this->data) || ! array_key_exists($rateKey, $this->data)) {
            return;
        }

        if (! $force && trim((string) ($this->data[$rateKey] ?? '')) !== ''
            && trim((string) ($this->data[$rateKey] ?? '')) !== ($this->autoRates[$rateKey] ?? null)) {
            return;
        }

        $currencies = app(CurrencyService::class);
        $currency = strtoupper(trim((string) ($this->data[$currencyKey] ?? ''))) ?: $currencies->base();

        $this->data[$currencyKey] = $currency;

        if ($currency === $currencies->base()) {
            $this->data[$rateKey] = '1';
            $this->autoRates[$rateKey] = '1';

            return;
        }

        $rate = app(ExchangeRateService::class)->rateForDate($currency, $this->data[$dateKey] ?? null);
        $this->data[$rateKey] = $rate !== null ? (string) $rate : '';
        $this->autoRates[$rateKey] = $this->data[$rateKey];
    }

    /**
     * Fire the domain transition event so audit/notification listeners run.
     * Best-effort: never block the user flow if the event fails.
     */
    protected function dispatchTransitioned(?string $fromStage, string $toStage): void
    {
        try {
            if (! class_exists(Vehicle::class) || ! Schema::hasTable('vehicles')) {
                return;
            }

            $vehicle = Vehicle::query()->where('vin', $this->vin)->first();

            if ($vehicle === null) {
                return;
            }

            $actor = auth()->user();
            $actor = $actor instanceof User ? $actor : null;

            VehicleTransitioned::dispatch($vehicle, $this->transitionKey, (string) ($fromStage ?? ''), $toStage, $actor);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render(CurrencyService $currencies)
    {
        $def = VehicleMeta::transitions()[$this->transitionKey] ?? null;
        abort_if(! $def, 400, __('vehicles.stage_unavailable'));

        return view('livewire.vehicles.transition-form', [
            'def' => $def,
            'labels' => VehicleMeta::fields(),
            'currencyOptions' => $currencies->options(true, array_filter([
                $this->data['shipping_currency'] ?? null,
                $this->data['sale_currency'] ?? null,
            ])),
            'proofCategory' => match ($this->transitionKey) {
                'ship' => AttachmentCategory::Shipping,
                'clear2' => AttachmentCategory::Landed,
                'sell' => AttachmentCategory::Sale,
                default => AttachmentCategory::General,
            },
        ]);
    }
}

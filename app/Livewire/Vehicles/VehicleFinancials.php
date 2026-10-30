<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Finance\Services\ExchangeRateService;
use App\Domain\Vehicle\Actions\UpdateVehicleDataAction;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\VehicleCostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Financial summary + role-scoped financial edit form for one vehicle.
 *
 * The form captures the native amount, its currency and the FX rate used for
 * that transaction. The base amount is computed and stored by the domain action.
 */
class VehicleFinancials extends Component
{
    use AuthorizesRequests;

    public string $vin = '';

    /** @var array<string, string> */
    public array $form = [];

    /** @var array<string, string> */
    protected array $messages = [];

    /**
     * Rates the component filled in itself, keyed by form field.
     *
     * @var array<string, string>
     */
    protected array $autoRates = [];

    public function mount(string $vin): void
    {
        $this->vin = $vin;
        $vehicle = $this->vehicle();

        $this->form = [
            'purchased_at' => $this->dateValue($vehicle->purchased_at),
            'cost' => $this->stringValue($vehicle->cost),
            'purchase_currency' => (string) ($vehicle->purchase_currency ?: app(CurrencyService::class)->base()),
            'purchase_fx_rate' => $this->stringValue($vehicle->purchase_fx_rate),
            'mod' => (string) ($vehicle->mod ?: 'No'),
            'modification_cost' => $this->stringValue($vehicle->modification_cost),
            'shipped_at' => $this->dateValue($vehicle->shipped_at),
            'shipping_cost' => $this->stringValue($vehicle->shipping_cost),
            'shipping_currency' => (string) ($vehicle->shipping_currency ?: app(CurrencyService::class)->base()),
            'shipping_fx_rate' => $this->stringValue($vehicle->shipping_fx_rate),
            'landed_cost' => $this->stringValue($vehicle->landed_cost),
            'sold_at' => $this->dateValue($vehicle->sold_at),
            'sale_price' => $this->stringValue($vehicle->sale_price),
            'sale_currency' => (string) ($vehicle->sale_currency ?: app(CurrencyService::class)->base()),
            'sale_fx_rate' => $this->stringValue($vehicle->sale_fx_rate),
            'amount_paid' => $this->stringValue($vehicle->amount_paid),
        ];

        $this->prefill('purchase');
        $this->prefill('shipping');
        $this->prefill('sale');
    }

    public function updated(string $name): void
    {
        if ($name === 'form.mod' && $this->form['mod'] !== 'Yes') {
            $this->form['modification_cost'] = '';
        }

        if (in_array($name, ['form.purchase_currency', 'form.purchased_at'], true)) {
            $this->prefill('purchase', $name === 'form.purchase_currency');
        }
        if (in_array($name, ['form.shipping_currency', 'form.shipped_at'], true)) {
            $this->prefill('shipping', $name === 'form.shipping_currency');
        }
        if (in_array($name, ['form.sale_currency', 'form.sold_at'], true)) {
            $this->prefill('sale', $name === 'form.sale_currency');
        }
    }

    public function save(UpdateVehicleDataAction $action): void
    {
        $vehicle = $this->vehicle();

        $payload = [];
        foreach ($this->form as $key => $value) {
            $payload[$key] = $value === '' ? null : $value;
        }

        try {
            $action->execute($vehicle, $payload, auth()->user());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError('form.'.$key, $message);
                }
            }

            return;
        }

        $this->mount($this->vin);
        session()->flash('financials-ok', __('app.saved'));
    }

    /** @return array<string, array{label: string, fields: array<int, array{key: string, type: string}>}> */
    public function fieldGroups(): array
    {
        return [
            'purchase' => [
                'label' => 'finance.purchase_section',
                'fields' => [
                    ['key' => 'purchased_at', 'type' => 'date'],
                    ['key' => 'cost', 'type' => 'number'],
                    ['key' => 'purchase_currency', 'type' => 'currency'],
                    ['key' => 'purchase_fx_rate', 'type' => 'number'],
                    ['key' => 'mod', 'type' => 'mod'],
                    ['key' => 'modification_cost', 'type' => 'number'],
                ],
            ],
            'shipping' => [
                'label' => 'finance.shipping_section',
                'fields' => [
                    ['key' => 'shipped_at', 'type' => 'date'],
                    ['key' => 'shipping_cost', 'type' => 'number'],
                    ['key' => 'shipping_currency', 'type' => 'currency'],
                    ['key' => 'shipping_fx_rate', 'type' => 'number'],
                ],
            ],
            'landed' => [
                'label' => 'finance.landed_section',
                'fields' => [
                    ['key' => 'landed_cost', 'type' => 'number'],
                ],
            ],
            'sale' => [
                'label' => 'finance.sale_section',
                'fields' => [
                    ['key' => 'sold_at', 'type' => 'date'],
                    ['key' => 'sale_price', 'type' => 'number'],
                    ['key' => 'sale_currency', 'type' => 'currency'],
                    ['key' => 'sale_fx_rate', 'type' => 'number'],
                    ['key' => 'amount_paid', 'type' => 'number'],
                ],
            ],
        ];
    }

    /** @return array<int, string> */
    public function editableFields(): array
    {
        return UpdateVehicleDataAction::editableFieldsForRole(auth()->user());
    }

    protected function prefill(string $prefix, bool $force = false): void
    {
        $map = [
            'purchase' => ['purchase_currency', 'purchased_at', 'purchase_fx_rate'],
            'shipping' => ['shipping_currency', 'shipped_at', 'shipping_fx_rate'],
            'sale' => ['sale_currency', 'sold_at', 'sale_fx_rate'],
        ];

        if (! isset($map[$prefix])) {
            return;
        }

        [$currencyKey, $dateKey, $rateKey] = $map[$prefix];

        $current = trim((string) ($this->form[$rateKey] ?? ''));
        $auto = $this->autoRates[$rateKey] ?? null;

        if (! $force && $current !== '' && $current !== $auto) {
            return;
        }

        $currencies = app(CurrencyService::class);
        $currency = strtoupper(trim((string) ($this->form[$currencyKey] ?? ''))) ?: $currencies->base();

        if ($currency === $currencies->base()) {
            $this->form[$rateKey] = '1';
            $this->autoRates[$rateKey] = '1';

            return;
        }

        $rate = app(ExchangeRateService::class)->rateForDate($currency, $this->form[$dateKey] ?: null);
        $this->form[$rateKey] = $rate !== null ? (string) $rate : '';
        $this->autoRates[$rateKey] = $this->form[$rateKey];
    }

    protected function vehicle(): Vehicle
    {
        return Vehicle::query()->where('vin', $this->vin)->firstOrFail();
    }

    protected function stringValue(mixed $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    protected function dateValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        try {
            return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
        } catch (\Throwable) {
            return '';
        }
    }

    public function render(VehicleCostService $costs, CurrencyService $currencies)
    {
        $vehicle = $this->vehicle();
        $breakdown = $costs->for($vehicle);

        return view('livewire.vehicles.vehicle-financials', [
            'breakdown' => $breakdown,
            'editable' => $this->editableFields(),
            'currencyOptions' => $currencies->options(true, array_filter([
                $vehicle->purchase_currency,
                $vehicle->shipping_currency,
                $vehicle->sale_currency,
            ])),
            'baseCurrency' => $currencies->base(),
            'fieldGroups' => $this->fieldGroups(),
        ]);
    }
}

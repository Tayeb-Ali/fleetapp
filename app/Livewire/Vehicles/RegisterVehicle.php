<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Finance\Services\ExchangeRateService;
use App\Domain\Vehicle\Actions\AttachFileAction;
use App\Domain\Vehicle\Actions\RegisterVehicleAction;
use App\Domain\Vehicle\Enums\AttachmentCategory;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Create form (purchase registration + purchase proof). */
class RegisterVehicle extends Component
{
    use WithFileUploads;

    public array $data = [];

    /** @var array<int, mixed> */
    public array $proofs = [];

    /** @var array<string, string> */
    protected array $messages = [];

    /**
     * Rates the component filled in itself, keyed by data field.
     *
     * @var array<string, string>
     */
    protected array $autoRates = [];

    public function mount(): void
    {
        $fields = config('fleet.create_fields', ['brand', 'model', 'vin', 'fuel', 'pdate', 'supplier', 'cost', 'bl', 'voy', 'pol', 'pod', 'ship', 'arr', 'remarks']);
        $this->data = array_fill_keys($fields, '');

        $base = app(CurrencyService::class)->base();

        foreach (['purchase_currency' => $base, 'purchase_fx_rate' => '1', 'mod' => 'No'] as $key => $value) {
            if (array_key_exists($key, $this->data)) {
                $this->data[$key] = $value;
            }
        }
    }

    public function updated(string $name, mixed $value = null): void
    {
        if ($name === 'data.mod' && $value !== 'Yes') {
            $this->data['modification_cost'] = '';
        }

        if ($name === 'data.purchase_currency') {
            $this->prefillPurchaseRate(true);
        } elseif ($name === 'data.pdate') {
            $this->prefillPurchaseRate();
        }
    }

    public function save(RegisterVehicleAction $action): mixed
    {
        $required = config('fleet.create_required', ['model', 'vin']);

        $this->validate([
            'data.model' => in_array('model', $required, true) ? 'required|string|max:300' : 'nullable|string|max:300',
            'data.vin' => in_array('vin', $required, true) ? 'required|string|max:300' : 'nullable|string|max:300',
            'proofs' => 'array|max:5',
            'proofs.*' => 'file|max:6144|mimes:pdf,png,jpg,jpeg,webp',
        ]);

        $vin = trim((string) ($this->data['vin'] ?? ''));
        if ($vin === '') {
            $this->addError('data.vin', __('actions.required').': '.__('vehicles.vin'));

            return null;
        }

        $payload = [];
        foreach ($this->data as $key => $value) {
            $clean = trim((string) $value);
            $payload[$key] = $clean === '' ? null : $clean;
        }

        if (($payload['mod'] ?? 'No') !== 'Yes') {
            $payload['modification_cost'] = null;
        }

        try {
            $vehicle = $action->execute($payload, auth()->user());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError('data.'.$key, $message);
                }
            }

            return null;
        }

        $attach = app(AttachFileAction::class);
        foreach ($this->proofs as $file) {
            try {
                $attach->execute($vehicle->vin, $file, auth()->id(), AttachmentCategory::Purchase);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('vehicles.show', $vehicle->vin);
    }

    protected function prefillPurchaseRate(bool $force = false): void
    {
        $currencies = app(CurrencyService::class);
        $currency = strtoupper(trim((string) ($this->data['purchase_currency'] ?? ''))) ?: $currencies->base();

        $current = trim((string) ($this->data['purchase_fx_rate'] ?? ''));
        if (! $force && $current !== '' && $current !== ($this->autoRates['purchase_fx_rate'] ?? null)) {
            return;
        }

        if ($currency === $currencies->base()) {
            $this->data['purchase_fx_rate'] = '1';
            $this->autoRates['purchase_fx_rate'] = '1';

            return;
        }

        $rate = app(ExchangeRateService::class)->rateForDate($currency, $this->data['pdate'] ?? null);
        $this->data['purchase_fx_rate'] = $rate !== null ? (string) $rate : '';
        $this->autoRates['purchase_fx_rate'] = $this->data['purchase_fx_rate'];
    }

    public function render(CurrencyService $currencies)
    {
        return view('livewire.vehicles.register-vehicle', [
            'fields' => config('fleet.create_fields', array_keys($this->data)),
            'required' => config('fleet.create_required', ['model', 'vin']),
            'labels' => config('fleet.fields', []),
            'currencyOptions' => $currencies->options(true, array_filter([
                $this->data['purchase_currency'] ?? null,
            ])),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Actions;

use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\FinancialFieldsResolver;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterVehicleAction
{
    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'brand' => 'nullable|string|max:255',
            'model' => 'required|string|max:255',
            'vin' => 'required|string|max:255|unique:vehicles,vin',
            'fuel' => 'nullable|string|max:255',
            'mod' => 'nullable|string|max:255',
            'purchased_at' => 'nullable|date',
            'supplier' => 'nullable|string|max:500',
            'cost' => 'nullable|numeric|min:0',
            'purchase_currency' => 'nullable|string|max:8',
            'purchase_fx_rate' => 'nullable|numeric|min:0.000001',
            'shipping_cost' => 'nullable|numeric|min:0',
            'shipping_currency' => 'nullable|string|max:8',
            'shipping_fx_rate' => 'nullable|numeric|min:0.000001',
            'landed_cost' => 'nullable|numeric|min:0',
            'modification_cost' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_currency' => 'nullable|string|max:8',
            'sale_fx_rate' => 'nullable|numeric|min:0.000001',
            'bl' => 'nullable|string|max:255',
            'voyage_no' => 'nullable|string|max:255',
            'pol' => 'nullable|string|max:255',
            'pod' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'shipped_at' => 'nullable|date',
            'arrived_at' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
        ];
    }

    /**
     * @param  array<string, mixed>  $data  (accepts legacy keys too)
     *
     * @throws ValidationException
     */
    public function execute(array $data, ?User $user = null): Vehicle
    {
        $data = $this->normalize($data);

        $validated = Validator::make($data, self::rules())->validate();

        // Empty strings crash decimal/date casts on read — store null.
        foreach ($validated as $key => $value) {
            if ($value === '') {
                $validated[$key] = null;
            }
        }

        // Currency / FX rate / converted base amounts (rate snapshot wins).
        $validated = app(FinancialFieldsResolver::class)->apply($validated);

        // Legacy line 154: stage auto — shipped when B/L present, else purchased.
        $validated['stage'] = ! empty($validated['bl'] ?? $data['bl'] ?? null)
            ? VehicleStage::Shipped->value
            : VehicleStage::Purchased->value;

        return Vehicle::create($validated);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    protected function normalize(array $data): array
    {
        foreach (Vehicle::LEGACY_MAP as $legacy => $column) {
            if (array_key_exists($legacy, $data) && ! array_key_exists($column, $data)) {
                $data[$column] = $data[$legacy];
            }
            unset($data[$legacy]);
        }

        return $data;
    }
}

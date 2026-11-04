<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Actions;

use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Services\FinancialFieldsResolver;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateVehicleDataAction
{
    /**
     * Editable DB columns per role (legacy EDIT map, line 14).
     * Admin may edit everything except vin (+ stage handled separately).
     *
     * @return array<string>
     */
    public static function editableFieldsForRole(User|string|null $user): array
    {
        $role = self::roleOf($user);

        return match ($role) {
            'admin' => [
                'brand', 'model', 'mod', 'fuel', 'purchased_at', 'supplier', 'cost',
                'purchase_currency', 'purchase_fx_rate',
                'bl', 'bl_release', 'voyage_no', 'pol', 'pod', 'destination', 'shipped_at', 'arrived_at',
                'shipping_cost', 'shipping_currency', 'shipping_fx_rate',
                'customs_no', 'customs_date', 'landed_cost', 'modification_cost',
                'warehouse_location', 'warehouse_in_at',
                'sale_order_no', 'buyer_name', 'buyer_phone', 'sale_price', 'sale_currency', 'sale_fx_rate', 'amount_paid', 'sold_at',
                'receiver_name', 'delivered_at',
                'remarks', 'stage',
            ],
            'purchaser' => [
                'brand', 'model', 'mod', 'fuel', 'purchased_at', 'supplier', 'cost',
                'purchase_currency', 'purchase_fx_rate', 'modification_cost',
                'bl', 'bl_release', 'voyage_no', 'pol', 'pod', 'shipped_at', 'arrived_at', 'remarks',
            ],
            'tracker' => ['bl', 'bl_release', 'voyage_no', 'pol', 'pod', 'destination', 'shipped_at', 'arrived_at', 'shipping_cost', 'shipping_currency', 'shipping_fx_rate', 'remarks'],
            'customs' => ['customs_no', 'customs_date', 'landed_cost', 'remarks'],
            'warehouse' => ['warehouse_location', 'warehouse_in_at', 'receiver_name', 'delivered_at', 'remarks'],
            'sales' => ['sale_order_no', 'buyer_name', 'buyer_phone', 'sale_price', 'sale_currency', 'sale_fx_rate', 'amount_paid', 'sold_at', 'remarks'],
            'accountant' => ['supplier', 'cost', 'purchase_currency', 'purchase_fx_rate', 'shipping_cost', 'shipping_currency', 'shipping_fx_rate', 'landed_cost', 'modification_cost', 'sale_price', 'sale_currency', 'sale_fx_rate', 'amount_paid', 'remarks'],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    public static function rulesForRole(User|string|null $user): array
    {
        $editable = self::editableFieldsForRole($user);

        $all = [
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'mod' => 'nullable|string|max:255',
            'fuel' => 'nullable|string|max:255',
            'purchased_at' => 'nullable|date',
            'supplier' => 'nullable|string|max:500',
            'cost' => 'nullable|numeric|min:0',
            'purchase_currency' => 'nullable|string|max:8',
            'purchase_fx_rate' => 'nullable|numeric|min:0.000001',
            'bl' => 'nullable|string|max:255',
            'bl_release' => 'nullable|in:Yes,No',
            'voyage_no' => 'nullable|string|max:255',
            'pol' => 'nullable|string|max:255',
            'pod' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'shipped_at' => 'nullable|date',
            'arrived_at' => 'nullable|date',
            'shipping_cost' => 'nullable|numeric|min:0',
            'shipping_currency' => 'nullable|string|max:8',
            'shipping_fx_rate' => 'nullable|numeric|min:0.000001',
            'customs_no' => 'nullable|string|max:255',
            'customs_date' => 'nullable|date',
            'landed_cost' => 'nullable|numeric|min:0',
            'modification_cost' => 'nullable|numeric|min:0',
            'warehouse_location' => 'nullable|string|max:255',
            'warehouse_in_at' => 'nullable|date',
            'sale_order_no' => 'nullable|string|max:255',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_phone' => 'nullable|string|max:255',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_currency' => 'nullable|string|max:8',
            'sale_fx_rate' => 'nullable|numeric|min:0.000001',
            'amount_paid' => 'nullable|numeric|min:0',
            'sold_at' => 'nullable|date',
            'receiver_name' => 'nullable|string|max:255',
            'delivered_at' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
            'stage' => 'nullable|string|in:'.implode(',', VehicleStage::values()),
        ];

        $rules = array_intersect_key($all, array_flip($editable));

        // sale_order_no uniqueness (ignore current vehicle) is added at execute() time.
        return $rules;
    }

    /**
     * @param  array<string, mixed>  $data  (accepts legacy keys too)
     *
     * @throws ValidationException
     */
    public function execute(Vehicle $vehicle, array $data, User|string|null $user = null): Vehicle
    {
        $data = $this->normalize($data);

        $editable = self::editableFieldsForRole($user);
        $data = array_intersect_key($data, array_flip($editable));

        $rules = self::rulesForRole($user);

        if (in_array('sale_order_no', $editable, true) && array_key_exists('sale_order_no', $data)) {
            $rules['sale_order_no'] = 'nullable|string|max:255|unique:vehicles,sale_order_no,'.$vehicle->vin.',vin';
        }

        $validated = Validator::make($data, $rules)->validate();

        // Empty strings crash decimal/date casts on read — store null.
        foreach ($validated as $key => $value) {
            if ($value === '') {
                $validated[$key] = null;
            }
        }

        // Currency / FX rate / converted base amounts (rate snapshot wins).
        $resolver = app(FinancialFieldsResolver::class);
        $validated = $resolver->apply($validated, $vehicle->toArray());

        if (($missing = $resolver->missingRate($validated)) !== null) {
            throw ValidationException::withMessages([
                $missing[0] => __('finance.errors.rate_required', ['currency' => $missing[1]]),
            ]);
        }

        if (array_key_exists('stage', $validated) && $validated['stage'] !== null) {
            $validated['stage'] = VehicleStage::from($validated['stage'])->value;
        }

        $vehicle->fill($validated);
        $vehicle->save();

        return $vehicle->fresh() ?? $vehicle;
    }

    public static function roleOf(User|string|null $user): ?string
    {
        if ($user instanceof User) {
            $role = $user->getAttribute('role');

            return is_string($role) ? $role : null;
        }

        return is_string($user) ? $user : null;
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

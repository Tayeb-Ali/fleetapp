<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Actions;

use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Models\Vehicle;
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

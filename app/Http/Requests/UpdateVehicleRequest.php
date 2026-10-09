<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Vehicle\Actions\UpdateVehicleDataAction;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        if (is_string($vehicle)) {
            $vehicle = Vehicle::find($vehicle);
        }

        if (! $vehicle instanceof Vehicle) {
            return false;
        }

        return $this->user()?->can('update', $vehicle) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return UpdateVehicleDataAction::rulesForRole($this->user());
    }
}

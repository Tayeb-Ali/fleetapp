<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Vehicle\Actions\RegisterVehicleAction;
use App\Domain\Vehicle\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vehicle::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return RegisterVehicleAction::rules();
    }
}

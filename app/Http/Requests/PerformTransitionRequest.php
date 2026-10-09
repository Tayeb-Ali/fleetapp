<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Support\TransitionRegistry;
use Illuminate\Foundation\Http\FormRequest;

class PerformTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->vehicle();
        $key = $this->transitionKey();

        if (! $vehicle || $key === '') {
            return false;
        }

        return $this->user()?->can('transition', [$vehicle, $key]) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $key = $this->transitionKey();

        if ($key === '') {
            return [];
        }

        try {
            $transition = app(TransitionRegistry::class)->get($key);
        } catch (\InvalidArgumentException) {
            return [];
        }

        $rules = [];
        foreach ($transition->fields() as $field) {
            $required = in_array($field, $transition->requiredFields(), true);
            $prefix = $required ? 'required' : 'nullable';

            $rules[$field] = match ($field) {
                'purchased_at', 'shipped_at', 'arrived_at', 'customs_date',
                'warehouse_in_at', 'sold_at', 'delivered_at' => "{$prefix}|date",
                'cost', 'duty', 'sale_price', 'amount_paid' => "{$prefix}|numeric|min:0",
                default => "{$prefix}|string|max:500",
            };
        }

        return $rules;
    }

    public function transitionKey(): string
    {
        $fromRoute = $this->route('transition');

        if (is_string($fromRoute) && $fromRoute !== '') {
            return $fromRoute;
        }

        return (string) $this->input('transition', '');
    }

    public function vehicle(): ?Vehicle
    {
        $param = $this->route('vehicle');

        if ($param instanceof Vehicle) {
            return $param;
        }

        if (is_string($param) && $param !== '') {
            return Vehicle::find($param);
        }

        return null;
    }
}

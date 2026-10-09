<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\UserRole;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class AbstractTransition implements TransitionInterface
{
    public function labelKey(): string
    {
        return "vehicle.transitions.{$this->key()}";
    }

    public function validateData(array $data): array
    {
        // Accept legacy short keys (bl, voy, ...) as well as new columns.
        $data = $this->normalizeKeys($data);

        $rules = [];
        foreach ($this->fields() as $field) {
            $rules[$field] = in_array($field, $this->requiredFields(), true)
                ? $this->ruleFor($field, true)
                : $this->ruleFor($field, false);
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function apply(Vehicle $vehicle, array $validated, ?User $user = null): Vehicle
    {
        $validated = $this->normalizeKeys($validated);

        return DB::transaction(function () use ($vehicle, $validated) {
            $vehicle->fill($validated);
            $vehicle->stage = $this->to();
            $vehicle->save();

            return $vehicle->fresh() ?? $vehicle;
        });
    }

    public function canApply(Vehicle $vehicle, User|string|null $user): bool
    {
        $stage = $vehicle->stageValue();

        if (! in_array($stage, $this->from(), true)) {
            return false;
        }

        $role = $this->resolveRole($user);

        if ($role === UserRole::Admin->value) {
            return true;
        }

        return in_array($role, $this->allowedRoles(), true);
    }

    /** Map legacy short keys to DB columns before validation/persist. */
    protected function normalizeKeys(array $data): array
    {
        foreach (Vehicle::LEGACY_MAP as $legacy => $column) {
            if (array_key_exists($legacy, $data) && ! array_key_exists($column, $data)) {
                $data[$column] = $data[$legacy];
            }
            unset($data[$legacy]);
        }

        return array_intersect_key($data, array_flip($this->fields()));
    }

    protected function resolveRole(User|string|null $user): ?string
    {
        if ($user instanceof User) {
            $role = $user->getAttribute('role');
            if ($role instanceof UserRole) {
                return $role->value;
            }

            return is_string($role) ? $role : null;
        }

        if ($user instanceof UserRole) {
            return $user->value;
        }

        return is_string($user) ? $user : null;
    }

    /** Basic rule per column type; required-ness handled by caller. */
    protected function ruleFor(string $field, bool $required): string|array
    {
        $prefix = $required ? 'required' : 'nullable';

        return match ($field) {
            'purchased_at', 'shipped_at', 'arrived_at', 'customs_date',
            'warehouse_in_at', 'sold_at', 'delivered_at' => "{$prefix}|date",
            'cost', 'duty', 'sale_price', 'amount_paid' => "{$prefix}|numeric|min:0",
            'sale_order_no' => $required ? 'required|string|max:255' : 'nullable|string|max:255',
            default => "{$prefix}|string|max:500",
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Policies;

use App\Domain\Vehicle\Actions\UpdateVehicleDataAction;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Support\TransitionRegistry;
use App\Models\User;

class VehiclePolicy
{
    protected function role(User $user): ?string
    {
        $role = $user->getAttribute('role');

        return is_string($role) ? $role : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($this->role($user), ['admin', 'purchaser'], true);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        $role = $this->role($user);

        if ($role === 'admin') {
            return true;
        }

        return count(UpdateVehicleDataAction::editableFieldsForRole($role)) > 0;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $this->role($user) === 'admin';
    }

    public function transition(User $user, Vehicle $vehicle, string $transitionKey): bool
    {
        $registry = app(TransitionRegistry::class);

        if (! $registry->has($transitionKey)) {
            return false;
        }

        $transition = $registry->get($transitionKey);

        $role = $this->role($user);

        if ($role === 'admin') {
            return in_array($vehicle->stageValue(), $transition->from(), true);
        }

        return $transition->canApply($vehicle, $role);
    }
}

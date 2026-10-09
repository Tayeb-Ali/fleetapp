<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Validation\ValidationException;

interface TransitionInterface
{
    public function key(): string;

    public function labelKey(): string;

    /** @return array<string> from-stage values */
    public function from(): array;

    public function to(): VehicleStage;

    /** @return array<string> allowed role values */
    public function allowedRoles(): array;

    /** @return array<string> writable DB columns */
    public function fields(): array;

    /** @return array<string> required DB columns */
    public function requiredFields(): array;

    /** @return array<string> roles to notify */
    public function notifyRoles(): array;

    /**
     * Filter $data to fields() + assert required fields are present.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> validated subset
     *
     * @throws ValidationException
     */
    public function validateData(array $data): array;

    /**
     * Persist field updates + stage change inside a DB transaction.
     * The caller is responsible for firing VehicleTransitioned / notifications.
     *
     * @param  array<string, mixed>  $validated
     */
    public function apply(Vehicle $vehicle, array $validated, ?User $user = null): Vehicle;

    public function canApply(Vehicle $vehicle, User|string|null $user): bool;
}

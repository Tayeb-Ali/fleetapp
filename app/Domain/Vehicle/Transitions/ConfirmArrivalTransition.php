<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\VehicleStage;

class ConfirmArrivalTransition extends AbstractTransition
{
    public function key(): string
    {
        return 'arrive';
    }

    public function from(): array
    {
        return [VehicleStage::Shipped->value];
    }

    public function to(): VehicleStage
    {
        return VehicleStage::Arrived;
    }

    public function allowedRoles(): array
    {
        return ['tracker'];
    }

    public function fields(): array
    {
        return ['arrived_at', 'pod'];
    }

    public function requiredFields(): array
    {
        return ['arrived_at'];
    }

    public function notifyRoles(): array
    {
        return ['customs'];
    }
}

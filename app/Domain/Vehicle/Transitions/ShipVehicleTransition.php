<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\VehicleStage;

class ShipVehicleTransition extends AbstractTransition
{
    public function key(): string
    {
        return 'ship';
    }

    public function from(): array
    {
        return [VehicleStage::Purchased->value];
    }

    public function to(): VehicleStage
    {
        return VehicleStage::Shipped;
    }

    public function allowedRoles(): array
    {
        return ['purchaser', 'tracker'];
    }

    public function fields(): array
    {
        return ['bl', 'voyage_no', 'pol', 'pod', 'destination', 'shipped_at', 'arrived_at'];
    }

    public function requiredFields(): array
    {
        return ['bl', 'shipped_at'];
    }

    public function notifyRoles(): array
    {
        return ['tracker'];
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\VehicleStage;

class DeliverVehicleTransition extends AbstractTransition
{
    public function key(): string
    {
        return 'deliver';
    }

    public function from(): array
    {
        return [VehicleStage::Sold->value];
    }

    public function to(): VehicleStage
    {
        return VehicleStage::Delivered;
    }

    public function allowedRoles(): array
    {
        return ['warehouse'];
    }

    public function fields(): array
    {
        return ['receiver_name', 'delivered_at'];
    }

    public function requiredFields(): array
    {
        return ['receiver_name', 'delivered_at'];
    }

    public function notifyRoles(): array
    {
        return ['admin', 'sales'];
    }
}

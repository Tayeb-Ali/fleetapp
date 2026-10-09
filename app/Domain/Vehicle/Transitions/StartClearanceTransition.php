<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\VehicleStage;

class StartClearanceTransition extends AbstractTransition
{
    public function key(): string
    {
        return 'clear1';
    }

    public function from(): array
    {
        return [VehicleStage::Arrived->value];
    }

    public function to(): VehicleStage
    {
        return VehicleStage::Clearing;
    }

    public function allowedRoles(): array
    {
        return ['customs'];
    }

    public function fields(): array
    {
        return ['customs_no'];
    }

    public function requiredFields(): array
    {
        return ['customs_no'];
    }

    public function notifyRoles(): array
    {
        return [];
    }
}

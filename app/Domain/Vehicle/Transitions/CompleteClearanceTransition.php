<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\VehicleStage;

class CompleteClearanceTransition extends AbstractTransition
{
    public function key(): string
    {
        return 'clear2';
    }

    public function from(): array
    {
        return [VehicleStage::Clearing->value];
    }

    public function to(): VehicleStage
    {
        return VehicleStage::Cleared;
    }

    public function allowedRoles(): array
    {
        return ['customs'];
    }

    public function fields(): array
    {
        return ['customs_no', 'customs_date', 'duty'];
    }

    public function requiredFields(): array
    {
        return ['customs_no', 'customs_date'];
    }

    public function notifyRoles(): array
    {
        return ['warehouse'];
    }
}

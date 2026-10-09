<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\VehicleStage;

class ReceiveIntoStockTransition extends AbstractTransition
{
    public function key(): string
    {
        return 'receive';
    }

    public function from(): array
    {
        return [VehicleStage::Cleared->value];
    }

    public function to(): VehicleStage
    {
        return VehicleStage::Instock;
    }

    public function allowedRoles(): array
    {
        return ['warehouse'];
    }

    public function fields(): array
    {
        return ['warehouse_location', 'warehouse_in_at'];
    }

    public function requiredFields(): array
    {
        return ['warehouse_location', 'warehouse_in_at'];
    }

    public function notifyRoles(): array
    {
        return ['sales'];
    }
}

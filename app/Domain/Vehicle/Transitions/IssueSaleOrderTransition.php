<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Transitions;

use App\Domain\Vehicle\Enums\VehicleStage;

class IssueSaleOrderTransition extends AbstractTransition
{
    public function key(): string
    {
        return 'sell';
    }

    public function from(): array
    {
        return [VehicleStage::Instock->value];
    }

    public function to(): VehicleStage
    {
        return VehicleStage::Sold;
    }

    public function allowedRoles(): array
    {
        return ['sales'];
    }

    public function fields(): array
    {
        return ['sale_order_no', 'buyer_name', 'buyer_phone', 'sale_price', 'amount_paid', 'sold_at'];
    }

    public function requiredFields(): array
    {
        return ['sale_order_no', 'buyer_name', 'sale_price', 'amount_paid'];
    }

    public function notifyRoles(): array
    {
        return ['warehouse', 'admin'];
    }
}

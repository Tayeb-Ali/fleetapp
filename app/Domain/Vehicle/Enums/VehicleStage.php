<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Enums;

enum VehicleStage: string
{
    case Purchased = 'purchased';
    case Shipped = 'shipped';
    case Arrived = 'arrived';
    case Clearing = 'clearing';
    case Cleared = 'cleared';
    case Instock = 'instock';
    case Sold = 'sold';
    case Delivered = 'delivered';

    public function label(): string
    {
        return __("vehicle.stages.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Purchased => 'gray',
            self::Shipped => 'blue',
            self::Arrived => 'indigo',
            self::Clearing => 'amber',
            self::Cleared => 'teal',
            self::Instock => 'green',
            self::Sold => 'orange',
            self::Delivered => 'emerald',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

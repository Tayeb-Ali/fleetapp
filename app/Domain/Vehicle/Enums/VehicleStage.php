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
            self::Purchased => '#94a3b8',
            self::Shipped => '#8b5cf6',
            self::Arrived => '#0ea5e9',
            self::Clearing => '#f59e0b',
            self::Cleared => '#f97316',
            self::Instock => '#14b8a6',
            self::Sold => '#ec4899',
            self::Delivered => '#22c55e',
        };
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}

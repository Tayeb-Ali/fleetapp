<?php

declare(strict_types=1);

namespace App\Domain\Vehicle;

/**
 * Static access to fleet registry with defensive fallbacks.
 * Prefers config/fleet.php, falls back to hardcoded legacy values.
 */
class VehicleMeta
{
    public static function stages(): array
    {
        $stages = config('fleet.stages');

        if (is_array($stages) && $stages !== []) {
            return $stages;
        }

        return [
            'purchased' => 'Purchased',
            'shipped' => 'In transit',
            'arrived' => 'Arrived at port',
            'clearing' => 'Clearing customs',
            'cleared' => 'Customs cleared',
            'instock' => 'In warehouse - available for sale',
            'sold' => 'Sold - awaiting delivery',
            'delivered' => 'Delivered',
        ];
    }

    public static function stageColors(): array
    {
        $colors = config('fleet.stage_colors');

        if (is_array($colors) && $colors !== []) {
            return $colors;
        }

        return [
            'purchased' => '#94a3b8',
            'shipped' => '#8b5cf6',
            'arrived' => '#0ea5e9',
            'clearing' => '#f59e0b',
            'cleared' => '#f97316',
            'instock' => '#14b8a6',
            'sold' => '#ec4899',
            'delivered' => '#22c55e',
        ];
    }

    public static function roles(): array
    {
        $roles = config('fleet.roles');

        if (is_array($roles) && $roles !== []) {
            return $roles;
        }

        return [
            'admin' => 'System Administrator',
            'purchaser' => 'Purchaser',
            'tracker' => 'Tracker',
            'customs' => 'Customs & Clearance',
            'sales' => 'Sales',
            'warehouse' => 'Warehouse',
            'viewer' => 'View only',
        ];
    }

    public static function fields(): array
    {
        $fields = config('fleet.fields');

        return is_array($fields) && $fields !== [] ? $fields : [];
    }

    public static function transitions(): array
    {
        $transitions = config('fleet.transitions');

        return is_array($transitions) && $transitions !== [] ? $transitions : [];
    }

    public static function transitionsFor(string $stage): array
    {
        return array_filter(
            self::transitions(),
            fn (array $t) => in_array($stage, (array) ($t['from'] ?? []), true)
        );
    }

    /** Localised stage label — prefers lang/stages.php, falls back to registry. */
    public static function stageLabel(string $stage): string
    {
        $key = 'stages.'.$stage;
        $label = __($key);

        if ($label !== $key) {
            return $label;
        }

        return self::stages()[$stage] ?? $stage;
    }
}

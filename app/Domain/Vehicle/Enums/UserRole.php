<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Enums;

use App\Models\User;

enum UserRole: string
{
    case Admin = 'admin';
    case Purchaser = 'purchaser';
    case Tracker = 'tracker';
    case Customs = 'customs';
    case Sales = 'sales';
    case Warehouse = 'warehouse';
    case Viewer = 'viewer';

    public function label(): string
    {
        return __("vehicle.roles.{$this->value}");
    }

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    public static function tryFromUser(mixed $user): ?self
    {
        $raw = null;

        if ($user instanceof User) {
            $raw = $user->getAttribute('role') ?? null;
        } elseif (is_string($user)) {
            $raw = $user;
        } elseif ($user instanceof self) {
            return $user;
        }

        if ($raw instanceof self) {
            return $raw;
        }

        return is_string($raw) ? self::tryFrom($raw) : null;
    }
}

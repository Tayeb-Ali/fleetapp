<?php

declare(strict_types=1);

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Notifications\FleetNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves notification recipients by role and dispatches FleetNotifications.
 *
 * Defensive: users.role / users.active may be missing on a half-migrated
 * database, so filtering is applied only when the columns exist.
 */
class FleetNotifier
{
    /**
     * Notify every active user holding one of the given roles.
     *
     * @param  array<int, string>  $roles
     */
    public function toRoles(array $roles, FleetNotification $notification, ?User $except = null): void
    {
        $roles = array_values(array_unique(array_filter(
            $roles,
            static fn (string $role): bool => $role !== ''
        )));

        if ($roles === []) {
            return;
        }

        $this->dispatch($notification, $roles, $except);
    }

    public function toAdmins(FleetNotification $notification, ?User $except = null): void
    {
        $this->dispatch($notification, ['admin'], $except);
    }

    /**
     * @param  array<int, string>  $roles
     */
    protected function dispatch(FleetNotification $notification, array $roles, ?User $except): void
    {
        try {
            $query = User::query();

            if (Schema::hasColumn('users', 'role')) {
                $query->whereIn('role', $roles);
            }

            if (Schema::hasColumn('users', 'active')) {
                $query->where('active', true);
            }

            if ($except !== null) {
                $query->whereKeyNot($except->getKey());
            }

            $users = $query->get();

            if ($users->isEmpty()) {
                return;
            }

            Notification::send($users, $notification);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

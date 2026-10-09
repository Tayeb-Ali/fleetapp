<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Listeners;

use App\Domain\Notification\Notifications\VehicleTransitionNotification;
use App\Domain\Vehicle\Events\VehicleTransitioned;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

/**
 * Notify users by role when a vehicle transitions.
 *
 * Legacy: notify(roles, vin, text) inserts role notifs + emails
 * active users with those roles (server.js 41-43).
 */
class SendTransitionNotifications
{
    public function handle(VehicleTransitioned $event): void
    {
        $query = User::query();

        // Defensive: users.role / users.active may be owned by another
        // worker and not exist yet. Filter only when columns exist.
        try {
            if (! empty($event->notifyRoles) && Schema::hasColumn('users', 'role')) {
                $query->whereIn('role', $event->notifyRoles);
            }
            if (Schema::hasColumn('users', 'active')) {
                $query->where('active', 1);
            }
        } catch (\Throwable) {
            // Fall through: notify whoever we can resolve.
        }

        try {
            $users = $query->get();
        } catch (\Throwable) {
            return;
        }

        if ($users->isEmpty()) {
            return;
        }

        $notification = new VehicleTransitionNotification(
            vin: $event->vin,
            action: $event->action,
            actorName: $event->actorName,
            fromStage: $event->fromStage,
            toStage: $event->toStage,
            note: $event->note,
        );

        // Queued: VehicleTransitionNotification implements ShouldQueue,
        // so both database + mail channels are queued (mail queued).
        Notification::send($users, $notification);
    }
}

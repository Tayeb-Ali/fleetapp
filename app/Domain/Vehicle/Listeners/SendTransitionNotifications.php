<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Listeners;

use App\Domain\Identity\Services\SettingsService;
use App\Domain\Notification\Notifications\FleetNotification;
use App\Domain\Notification\Services\FleetNotifier;
use App\Domain\Vehicle\Events\VehicleTransitioned;
use App\Domain\Vehicle\Support\TransitionRegistry;

/**
 * Notify configured roles when a vehicle transitions.
 *
 * Recipient roles come from Admin Settings (per-transition override), then the
 * transition definition, then config. Admins are always included so the system
 * owner keeps a full view of every movement.
 */
class SendTransitionNotifications
{
    public function __construct(
        protected TransitionRegistry $registry,
        protected SettingsService $settings,
        protected FleetNotifier $notifier,
    ) {}

    public function handle(VehicleTransitioned $event): void
    {
        $vehicle = $event->vehicle;

        $notification = new FleetNotification('vehicle', 'transitioned', [
            'vin' => $vehicle->vin,
            'vehicle' => trim((string) ($vehicle->brand ?? '').' '.(string) ($vehicle->model ?? '')),
            'transition_key' => $event->transitionKey,
            'from_stage' => $event->fromValue(),
            'to_stage' => $event->toValue(),
            'actor' => $event->user?->name,
            'url' => route('vehicles.show', $vehicle->vin, false),
        ]);

        $this->notifier->toRoles($this->roles($event->transitionKey), $notification);
    }

    /**
     * Resolve the notify roles for a transition key.
     *
     * @return array<int, string>
     */
    protected function roles(string $key): array
    {
        $roles = ['admin'];

        try {
            $override = $this->settings->getTransitionPermissions()[$key]['notify'] ?? null;

            if (is_array($override) && $override !== []) {
                return array_values(array_unique(array_merge($roles, $override)));
            }
        } catch (\Throwable) {
            // Fall through to the transition definition.
        }

        try {
            return array_values(array_unique(array_merge($roles, $this->registry->get($key)->notifyRoles())));
        } catch (\Throwable) {
            $fallback = config("fleet.transitions.{$key}.notify");

            return array_values(array_unique(array_merge($roles, is_array($fallback) ? $fallback : [])));
        }
    }
}

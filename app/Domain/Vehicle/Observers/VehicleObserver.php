<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Observers;

use App\Domain\Notification\Notifications\FleetNotification;
use App\Domain\Notification\Services\FleetNotifier;
use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Models\VehicleActivity;

class VehicleObserver
{
    public function created(Vehicle $vehicle): void
    {
        VehicleActivity::create([
            'vin' => $vehicle->vin,
            'action' => 'created',
            'from_stage' => null,
            'to_stage' => $vehicle->stageValue(),
            'note' => 'Vehicle purchase registered',
        ]);

        $this->notifyAdmins('created', $vehicle);
    }

    public function updated(Vehicle $vehicle): void
    {
        if ($vehicle->wasChanged('stage')) {
            VehicleActivity::create([
                'vin' => $vehicle->vin,
                'action' => 'transition',
                'from_stage' => $vehicle->getOriginal('stage') instanceof VehicleStage
                    ? $vehicle->getOriginal('stage')->value
                    : (string) $vehicle->getOriginal('stage'),
                'to_stage' => $vehicle->stageValue(),
                'note' => 'Stage changed',
            ]);

            return;
        }

        VehicleActivity::create([
            'vin' => $vehicle->vin,
            'action' => 'updated',
            'from_stage' => $vehicle->stageValue(),
            'to_stage' => $vehicle->stageValue(),
            'note' => 'Data updated',
        ]);
    }

    public function deleted(Vehicle $vehicle): void
    {
        if ($vehicle->isForceDeleting()) {
            return;
        }

        VehicleActivity::create([
            'vin' => $vehicle->vin,
            'action' => 'deleted',
            'from_stage' => $vehicle->stageValue(),
            'to_stage' => null,
            'note' => 'Vehicle deleted',
        ]);

        $this->notifyAdmins('deleted', $vehicle);
    }

    public function restored(Vehicle $vehicle): void
    {
        VehicleActivity::create([
            'vin' => $vehicle->vin,
            'action' => 'restored',
            'from_stage' => null,
            'to_stage' => $vehicle->stageValue(),
            'note' => 'Vehicle restored',
        ]);

        $this->notifyAdmins('restored', $vehicle);
    }

    /**
     * Notify admins about an interactive vehicle lifecycle change.
     * Skipped for console/import writes where no user is authenticated.
     */
    protected function notifyAdmins(string $event, Vehicle $vehicle): void
    {
        if (! auth()->check()) {
            return;
        }

        try {
            app(FleetNotifier::class)->toAdmins(new FleetNotification('vehicle', $event, [
                'vin' => $vehicle->vin,
                'vehicle' => trim((string) ($vehicle->brand ?? '').' '.(string) ($vehicle->model ?? '')),
                'to_stage' => $event === 'deleted' ? null : $vehicle->stageValue(),
                'actor' => auth()->user()->name ?? null,
                'url' => route('vehicles.show', $vehicle->vin, false),
            ]));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

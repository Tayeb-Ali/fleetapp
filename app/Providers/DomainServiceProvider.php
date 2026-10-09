<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Observers\VehicleObserver;
use App\Domain\Vehicle\Policies\VehiclePolicy;
use App\Domain\Vehicle\Support\TransitionRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TransitionRegistry::class, fn () => new TransitionRegistry);
    }

    public function boot(): void
    {
        Gate::policy(Vehicle::class, VehiclePolicy::class);

        Vehicle::observe(VehicleObserver::class);
    }
}

<?php

namespace App\Providers;

use App\Domain\Identity\Policies\UserPolicy;
use App\Domain\Vehicle\Events\VehicleTransitioned;
use App\Domain\Vehicle\Listeners\SendTransitionNotifications;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Event::listen(VehicleTransitioned::class, SendTransitionNotifications::class);
    }
}

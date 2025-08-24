<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\UnitContract;
use App\Models\PropertyContract;
use App\Observers\UnitContractObserver;
use App\Policies\PropertyContractPolicy;
use App\Policies\UnitContractPolicy;

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
        // Register Policies
        Gate::policy(PropertyContract::class, PropertyContractPolicy::class);
        Gate::policy(UnitContract::class, UnitContractPolicy::class);
        
        // Register Observers
        UnitContract::observe(UnitContractObserver::class);
    }
}

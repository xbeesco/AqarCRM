<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use App\Models\UnitContract;
use App\Models\PropertyContract;
use App\Observers\UnitContractObserver;
use App\Observers\PropertyContractObserver;
use App\Policies\PropertyContractPolicy;
use App\Policies\UnitContractPolicy;
use App\Helpers\DateHelper;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

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
        // Initialize test date system
        $this->initializeTestDateSystem();
        
        // Register Policies
        Gate::policy(PropertyContract::class, PropertyContractPolicy::class);
        Gate::policy(UnitContract::class, UnitContractPolicy::class);
        
        // Register Observers
        UnitContract::observe(UnitContractObserver::class);
        PropertyContract::observe(PropertyContractObserver::class);
        
        // Register test date indicator before global search
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("filament.test-date-indicator")')
        );
    }
    
    /**
     * Initialize the test date system
     */
    protected function initializeTestDateSystem(): void
    {
        DateHelper::initializeTestDate();
    }
}
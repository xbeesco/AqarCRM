<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\Tenant;
use App\Policies\UserPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\OwnerPolicy;
use App\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Employee::class => EmployeePolicy::class,
        Owner::class => OwnerPolicy::class,
        Tenant::class => TenantPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates
        Gate::define('manage-system', function (User $user) {
            return $user->type === 'super_admin';
        });

        Gate::define('manage-users', function (User $user) {
            return in_array($user->type, ['super_admin', 'admin']);
        });

        Gate::define('access-admin-panel', function (User $user) {
            return in_array($user->type, ['super_admin', 'admin', 'employee']);
        });

        Gate::define('view-trashed-records', function (User $user) {
            return $user->type === 'super_admin';
        });

        Gate::define('export-data', function (User $user) {
            return in_array($user->type, ['super_admin', 'admin']);
        });

        Gate::define('modify-deleted-records', function (User $user) {
            return $user->type === 'super_admin';
        });
        
        Gate::define('global-search', function (User $user) {
            return in_array($user->type, ['super_admin', 'admin', 'employee']);
        });
    }
}
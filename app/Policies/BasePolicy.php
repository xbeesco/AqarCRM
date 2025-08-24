<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Super admins can do everything
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->type === 'super_admin') {
            return true;
        }

        return null;
    }

    /**
     * Check if user is admin or super admin
     */
    protected function isAdmin(User $user): bool
    {
        return in_array($user->type, ['super_admin', 'admin']);
    }

    /**
     * Check if user is employee
     */
    protected function isEmployee(User $user): bool
    {
        return $user->type === 'employee';
    }

    /**
     * Check if user is owner
     */
    protected function isOwner(User $user): bool
    {
        return $user->type === 'owner';
    }

    /**
     * Check if user is tenant
     */
    protected function isTenant(User $user): bool
    {
        return $user->type === 'tenant';
    }

    /**
     * Log unauthorized access attempt
     */
    protected function logUnauthorizedAccess(User $user, string $action, $model = null): void
    {
        \Log::warning('Unauthorized access attempt', [
            'user_id' => $user->id,
            'user_type' => $user->type,
            'action' => $action,
            'model' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
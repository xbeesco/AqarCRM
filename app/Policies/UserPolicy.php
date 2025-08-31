<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    /**
     * View any users (list page)
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view all users list
        if (!$this->isAdmin($user)) {
            $this->logUnauthorizedAccess($user, 'viewAny', User::class);
            return false;
        }
        return true;
    }

    /**
     * View specific user
     */
    public function view(User $user, User $model): bool
    {
        // Admins can view all users
        if ($this->isAdmin($user)) {
            return true;
        }
        
        // Users can only view their own profile
        if ($user->id === $model->id) {
            return true;
        }
        
        $this->logUnauthorizedAccess($user, 'view', $model);
        return false;
    }

    /**
     * Create new user
     */
    public function create(User $user): bool
    {
        // Only admins can create users
        if (!$this->isAdmin($user)) {
            $this->logUnauthorizedAccess($user, 'create', User::class);
            return false;
        }
        return true;
    }

    /**
     * Update user
     */
    public function update(User $user, User $model): bool
    {
        // Super admin can update anyone
        if ($user->type === 'super_admin') {
            return true;
        }
        
        // Admin cannot update super admin or other admins
        if ($user->type === 'admin' && in_array($model->type, ['super_admin', 'admin'])) {
            $this->logUnauthorizedAccess($user, 'update', $model);
            return false;
        }
        
        // Admin can update employees, owners, tenants
        if ($user->type === 'admin' && in_array($model->type, ['employee', 'owner', 'tenant'])) {
            return true;
        }
        
        // Users can update their own profile (limited fields)
        if ($user->id === $model->id) {
            return true;
        }
        
        $this->logUnauthorizedAccess($user, 'update', $model);
        return false;
    }

    /**
     * Delete user
     */
    public function delete(User $user, User $model): bool
    {
        // Super admin can delete anyone except themselves
        if ($user->type === 'super_admin' && $user->id !== $model->id) {
            return true;
        }
        
        // Admin cannot delete super admin, other admins, or themselves
        if ($user->type === 'admin' && 
            in_array($model->type, ['super_admin', 'admin']) ||
            $user->id === $model->id) {
            $this->logUnauthorizedAccess($user, 'delete', $model);
            return false;
        }
        
        // Admin can delete employees, owners, tenants
        if ($user->type === 'admin' && in_array($model->type, ['employee', 'owner', 'tenant'])) {
            return true;
        }
        
        $this->logUnauthorizedAccess($user, 'delete', $model);
        return false;
    }

    /**
     * Restore deleted user
     */
    public function restore(User $user, User $model): bool
    {
        // Only super admin can restore users
        if ($user->type !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'restore', $model);
            return false;
        }
        return true;
    }

    /**
     * Force delete user
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only super admin can permanently delete
        if ($user->type !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'forceDelete', $model);
            return false;
        }
        return true;
    }

    /**
     * Change user type/role
     */
    public function changeType(User $user, User $model, string $newType): bool
    {
        // Only super admin can change user types
        if ($user->type !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'changeType', $model);
            return false;
        }
        
        // Cannot change own type to lower level
        if ($user->id === $model->id && $newType !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'changeType', $model);
            return false;
        }
        
        return true;
    }

    /**
     * Access trashed users
     */
    public function viewTrashed(User $user): bool
    {
        // Only super admin can view trashed users
        if ($user->type !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'viewTrashed', User::class);
            return false;
        }
        return true;
    }
}
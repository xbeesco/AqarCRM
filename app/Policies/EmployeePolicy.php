<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;

class EmployeePolicy extends BasePolicy
{
    /**
     * View any employees
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view employees list
        if (!$this->isAdmin($user)) {
            $this->logUnauthorizedAccess($user, 'viewAny', Employee::class);
            return false;
        }
        return true;
    }

    /**
     * View specific employee
     */
    public function view(User $user, Employee $model): bool
    {
        // Admins can view all employees
        if ($this->isAdmin($user)) {
            return true;
        }
        
        // Employee can view their own profile
        if ($user->id === $model->id) {
            return true;
        }
        
        $this->logUnauthorizedAccess($user, 'view', $model);
        return false;
    }

    /**
     * Create new employee
     */
    public function create(User $user): bool
    {
        // Super admin and admin can create employees
        if (!$this->isAdmin($user)) {
            $this->logUnauthorizedAccess($user, 'create', Employee::class);
            return false;
        }
        return true;
    }

    /**
     * Update employee
     */
    public function update(User $user, Employee $model): bool
    {
        // Super admin can update any employee
        if ($user->type === 'super_admin') {
            return true;
        }
        
        // Admin cannot update super admin or other admin employees
        if ($user->type === 'admin' && in_array($model->type, ['super_admin', 'admin'])) {
            $this->logUnauthorizedAccess($user, 'update', $model);
            return false;
        }
        
        // Admin can update regular employees
        if ($user->type === 'admin' && $model->type === 'employee') {
            return true;
        }
        
        // Employee can update limited fields of their own profile
        if ($user->id === $model->id) {
            return true;
        }
        
        $this->logUnauthorizedAccess($user, 'update', $model);
        return false;
    }

    /**
     * Delete employee
     */
    public function delete(User $user, Employee $model): bool
    {
        // Super admin and admin can delete employees
        if (!$this->isAdmin($user)) {
            $this->logUnauthorizedAccess($user, 'delete', $model);
            return false;
        }

        // Cannot delete self
        if ($user->id === $model->id) {
            $this->logUnauthorizedAccess($user, 'delete', $model);
            return false;
        }

        // Admin cannot delete super_admin or other admin
        if ($user->type === 'admin' && in_array($model->type, ['super_admin', 'admin'])) {
            $this->logUnauthorizedAccess($user, 'delete', $model);
            return false;
        }

        return true;
    }

    /**
     * Change employee type/role
     */
    public function changeType(User $user, Employee $model, string $newType): bool
    {
        // Only super admin can promote/demote employees
        if ($user->type !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'changeType', $model);
            return false;
        }
        
        // Cannot demote self from super_admin
        if ($user->id === $model->id && $user->type === 'super_admin' && $newType !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'changeType', $model);
            return false;
        }
        
        return true;
    }
}
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Owner;

class OwnerPolicy extends BasePolicy
{
    /**
     * View any owners
     */
    public function viewAny(User $user): bool
    {
        // Employees and above can view owners
        if (!in_array($user->type, ['super_admin', 'admin', 'employee'])) {
            $this->logUnauthorizedAccess($user, 'viewAny', Owner::class);
            return false;
        }
        return true;
    }

    /**
     * View specific owner
     */
    public function view(User $user, Owner $model): bool
    {
        // Admins and employees can view any owner
        if (in_array($user->type, ['super_admin', 'admin', 'employee'])) {
            return true;
        }

        // Owner can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'view', $model);
        return false;
    }

    /**
     * Create new owner
     */
    public function create(User $user): bool
    {
        // Admins and employees can create owners
        return in_array($user->type, ['super_admin', 'admin', 'employee']);
    }

    /**
     * Update owner
     */
    public function update(User $user, Owner $model): bool
    {
        // Admins and employees can update owners
        if (in_array($user->type, ['super_admin', 'admin', 'employee'])) {
            return true;
        }

        // Owner can update limited fields of their own profile
        if ($user->id === $model->id) {
            return true;
        }
        
        $this->logUnauthorizedAccess($user, 'update', $model);
        return false;
    }

    /**
     * Delete owner
     */
    public function delete(User $user, Owner $model): bool
    {
        // Only admins can delete owners
        if (!$this->isAdmin($user)) {
            $this->logUnauthorizedAccess($user, 'delete', $model);
            return false;
        }
        
        // TODO: Check if owner has active properties/contracts
        // Should not delete owner with active business
        
        return true;
    }

    /**
     * View owner financial reports
     */
    public function viewFinancialReports(User $user, Owner $model): bool
    {
        // Admins can view any owner's reports
        if ($this->isAdmin($user)) {
            return true;
        }
        
        // Owner can view their own reports
        if ($user->id === $model->id) {
            return true;
        }
        
        $this->logUnauthorizedAccess($user, 'viewFinancialReports', $model);
        return false;
    }

    /**
     * Manage owner's properties
     */
    public function manageProperties(User $user, Owner $model): bool
    {
        // Admins and employees can manage any owner's properties
        if (in_array($user->type, ['super_admin', 'admin', 'employee'])) {
            return true;
        }
        
        // Owner cannot manage their properties directly (must go through admin)
        $this->logUnauthorizedAccess($user, 'manageProperties', $model);
        return false;
    }
}
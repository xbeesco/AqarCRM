<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy extends BasePolicy
{
    /**
     * View any tenants
     */
    public function viewAny(User $user): bool
    {
        // Employees and above can view tenants
        if (! in_array($user->type, ['super_admin', 'admin', 'employee'])) {
            $this->logUnauthorizedAccess($user, 'viewAny', Tenant::class);

            return false;
        }

        return true;
    }

    /**
     * View specific tenant
     */
    public function view(User $user, Tenant $model): bool
    {
        // Admins and employees can view any tenant
        if (in_array($user->type, ['super_admin', 'admin', 'employee'])) {
            return true;
        }

        // Tenant can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'view', $model);

        return false;
    }

    /**
     * Create new tenant
     */
    public function create(User $user): bool
    {
        // Only admins can create tenants
        if (! $this->isAdmin($user)) {
            $this->logUnauthorizedAccess($user, 'create', Tenant::class);

            return false;
        }

        return true;
    }

    /**
     * Update tenant
     */
    public function update(User $user, Tenant $model): bool
    {
        // Admins can update any tenant
        if ($this->isAdmin($user)) {
            return true;
        }

        // Employees cannot update tenants
        if ($user->type === 'employee') {
            $this->logUnauthorizedAccess($user, 'update', $model);

            return false;
        }

        // Tenant can update limited fields of their own profile
        if ($user->id === $model->id) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'update', $model);

        return false;
    }

    /**
     * Delete tenant
     */
    public function delete(User $user, Tenant $model): bool
    {
        // Only admins can delete tenants
        if (! $this->isAdmin($user)) {
            $this->logUnauthorizedAccess($user, 'delete', $model);

            return false;
        }

        // TODO: Check if tenant has active contracts
        // Should not delete tenant with active lease

        return true;
    }

    /**
     * View tenant financial records
     */
    public function viewFinancialRecords(User $user, Tenant $model): bool
    {
        // Admins can view any tenant's financial records
        if ($this->isAdmin($user)) {
            return true;
        }

        // Tenant can view their own financial records
        if ($user->id === $model->id) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'viewFinancialRecords', $model);

        return false;
    }

    /**
     * Manage tenant's contracts
     */
    public function manageContracts(User $user, Tenant $model): bool
    {
        // Admins and employees can manage any tenant's contracts
        if (in_array($user->type, ['super_admin', 'admin', 'employee'])) {
            return true;
        }

        // Tenant cannot manage their contracts directly (read-only access)
        $this->logUnauthorizedAccess($user, 'manageContracts', $model);

        return false;
    }

    /**
     * Make payments
     */
    public function makePayments(User $user, Tenant $model): bool
    {
        // Admins can record payments for any tenant
        if ($this->isAdmin($user)) {
            return true;
        }

        // Tenant can view their own payment records but not modify them
        // All payments must be processed by admin/employee
        if ($user->id === $model->id) {
            $this->logUnauthorizedAccess($user, 'makePayments', $model);

            return false;
        }

        $this->logUnauthorizedAccess($user, 'makePayments', $model);

        return false;
    }
}

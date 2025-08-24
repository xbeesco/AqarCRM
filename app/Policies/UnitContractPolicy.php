<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UnitContract;

class UnitContractPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Everyone except tenants can view contracts list
        // Tenants can only see their own
        return match($user->type) {
            'super_admin', 'admin', 'employee', 'owner' => true,
            'tenant' => true, // Will be filtered in query
            default => false,
        };
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UnitContract $contract): bool
    {
        return match($user->type) {
            'super_admin', 'admin', 'employee' => true,
            'owner' => $contract->property?->owner_id === $user->id,
            'tenant' => $contract->tenant_id === $user->id,
            default => false,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins and employees can create tenant contracts
        return $this->isAdmin($user) || $this->isEmployee($user);
    }

    /**
     * Determine whether the user can update the model.
     * ⚠️ Only super_admin can update contracts
     */
    public function update(User $user, UnitContract $contract): bool
    {
        // Super admin handled in before() method
        
        // Log attempt for non-super admins
        if ($user->type !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'update_unit_contract', $contract);
        }
        
        // Others cannot update
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * ⚠️ Only super_admin can delete contracts
     */
    public function delete(User $user, UnitContract $contract): bool
    {
        // Super admin handled in before() method
        
        // Log attempt for non-super admins
        if ($user->type !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'delete_unit_contract', $contract);
        }
        
        // Others cannot delete
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UnitContract $contract): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UnitContract $contract): bool
    {
        // Log the attempt
        $this->logUnauthorizedAccess($user, 'force_delete_unit_contract', $contract);
        
        return false;
    }

    /**
     * Determine whether the user can terminate the contract.
     * Only admins can terminate contracts
     */
    public function terminate(User $user, UnitContract $contract): bool
    {
        return $this->isAdmin($user) && $contract->contract_status === 'active';
    }

    /**
     * Determine whether the user can approve the contract.
     * Only admins can approve contracts
     */
    public function approve(User $user, UnitContract $contract): bool
    {
        return $this->isAdmin($user) && $contract->contract_status === 'draft';
    }

    /**
     * Determine whether the user can renew the contract.
     * Admins and employees can renew
     */
    public function renew(User $user, UnitContract $contract): bool
    {
        return ($this->isAdmin($user) || $this->isEmployee($user)) 
            && in_array($contract->contract_status, ['active', 'expired']);
    }
}
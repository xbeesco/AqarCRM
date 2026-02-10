<?php

namespace App\Policies;

use App\Models\UnitContract;
use App\Models\User;

class UnitContractPolicy extends BasePolicy
{
    /**
     * Override before() to prevent super_admin from updating/deleting contracts
     * Contracts are immutable records that no one should modify or delete
     */
    public function before(User $user, string $ability): ?bool
    {
        // Block update and delete for everyone, including super_admin
        if (in_array($ability, ['update', 'delete', 'forceDelete'])) {
            return null; // Let the specific policy method decide (which will return false)
        }

        // For all other abilities, super_admin has full access
        if ($user->type === 'super_admin') {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Everyone except tenants can view contracts list
        // Tenants can only see their own
        return match ($user->type) {
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
        return match ($user->type) {
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
     * ⚠️ No one can update contracts - contracts are immutable
     */
    public function update(User $user, UnitContract $contract): bool
    {
        // Log the attempt
        $this->logUnauthorizedAccess($user, 'update_unit_contract', $contract);

        // No one can update contracts
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * ⚠️ No one can delete contracts - contracts are permanent records
     */
    public function delete(User $user, UnitContract $contract): bool
    {
        // Log the attempt
        $this->logUnauthorizedAccess($user, 'delete_unit_contract', $contract);

        // No one can delete contracts
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
        return in_array($user->type, ['super_admin', 'admin', 'employee']) && $contract->contract_status === 'active';
    }

    /**
     * Determine whether the user can approve the contract.
     * Only admins can approve contracts
     */
    public function approve(User $user, UnitContract $contract): bool
    {
        return in_array($user->type, ['super_admin', 'admin', 'employee']) && $contract->contract_status === 'draft';
    }

    /**
     * Determine whether the user can renew the contract.
     * Admins and employees can renew active contracts only
     */
    public function renew(User $user, UnitContract $contract): bool
    {
        return ($this->isAdmin($user) || $this->isEmployee($user))
            && $contract->contract_status === 'active';
    }

    /**
     * Determine whether the user can reschedule payments.
     * Admins and employees can reschedule payments
     */
    public function reschedule(User $user, UnitContract $contract): bool
    {
        return ($this->isAdmin($user) || $this->isEmployee($user))
            && $contract->canBeRescheduled();
    }
}

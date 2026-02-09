<?php

namespace App\Policies;

use App\Models\PropertyContract;
use App\Models\User;

class PropertyContractPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins, employees can view all
        // Owners can only view their own contracts
        return match ($user->type) {
            'super_admin', 'admin', 'employee' => true,
            'owner' => true, // Will be filtered in query
            default => false,
        };
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PropertyContract $contract): bool
    {
        return match ($user->type) {
            'super_admin', 'admin', 'employee' => true,
            'owner' => $contract->owner_id === $user->id,
            default => false,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admins and employees can create contracts
        return in_array($user->type, ['super_admin', 'admin', 'employee']);
    }

    /**
     * Determine whether the user can update the model.
     * ⚠️ Only super_admin can update contracts
     */
    public function update(User $user, PropertyContract $contract): bool
    {
        // Super admins, admins and employees can update contracts
        return in_array($user->type, ['super_admin', 'admin', 'employee']);
    }

    /**
     * Determine whether the user can delete the model.
     * ⚠️ Only super_admin can delete contracts
     */
    public function delete(User $user, PropertyContract $contract): bool
    {
        // Super admin handled in before() method

        // Log attempt for non-super admins
        if ($user->type !== 'super_admin') {
            $this->logUnauthorizedAccess($user, 'delete_property_contract', $contract);
        }

        // Others cannot delete
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PropertyContract $contract): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PropertyContract $contract): bool
    {
        // Log the attempt
        $this->logUnauthorizedAccess($user, 'force_delete_property_contract', $contract);

        return false;
    }

    /**
     * Determine whether the user can terminate the contract.
     * Only admins can terminate contracts
     */
    public function terminate(User $user, PropertyContract $contract): bool
    {
        return in_array($user->type, ['super_admin', 'admin', 'employee']) && $contract->contract_status === 'active';
    }

    /**
     * Determine whether the user can approve the contract.
     * Only admins can approve contracts
     */
    public function approve(User $user, PropertyContract $contract): bool
    {
        return in_array($user->type, ['super_admin', 'admin', 'employee']) && $contract->contract_status === 'draft';
    }

    /**
     * Determine whether the user can reschedule payments.
     * Admins and employees can reschedule payments
     */
    public function reschedule(User $user, PropertyContract $contract): bool
    {
        return in_array($user->type, ['super_admin', 'admin', 'employee'])
            && $contract->canBeRescheduled();
    }

    /**
     * Determine whether the user can renew the contract.
     * Admins and employees can renew
     */
    public function renew(User $user, PropertyContract $contract): bool
    {
        return in_array($user->type, ['super_admin', 'admin', 'employee'])
            && $contract->canBeRescheduled();
    }
}

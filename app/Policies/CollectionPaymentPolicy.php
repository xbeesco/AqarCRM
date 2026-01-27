<?php

namespace App\Policies;

use App\Models\CollectionPayment;
use App\Models\User;

class CollectionPaymentPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins and employees can view all payments
        if ($this->isAdmin($user) || $this->isEmployee($user)) {
            return true;
        }

        // Owners and tenants can view the list (filtered in resource)
        if ($this->isOwner($user) || $this->isTenant($user)) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'viewAny', CollectionPayment::class);

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CollectionPayment $collectionPayment): bool
    {
        // Admins and employees can view any payment
        if ($this->isAdmin($user) || $this->isEmployee($user)) {
            return true;
        }

        // Owners can view payments for their properties
        if ($this->isOwner($user)) {
            $propertyOwner = $collectionPayment->property?->owner_id;
            if ($propertyOwner === $user->id) {
                return true;
            }
        }

        // Tenants can view their own payments
        if ($this->isTenant($user) && $collectionPayment->tenant_id === $user->id) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'view', $collectionPayment);

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only admins and employees can create payments
        if ($this->isAdmin($user) || $this->isEmployee($user)) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'create', CollectionPayment::class);

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CollectionPayment $collectionPayment): bool
    {
        // Only admins and employees can update payments
        if ($this->isAdmin($user) || $this->isEmployee($user)) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'update', $collectionPayment);

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CollectionPayment $collectionPayment): bool
    {
        // Only admins can delete payments (financial data should be protected)
        if ($this->isAdmin($user)) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'delete', $collectionPayment);

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CollectionPayment $collectionPayment): bool
    {
        // Only admins can restore deleted payments
        if ($this->isAdmin($user)) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'restore', $collectionPayment);

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CollectionPayment $collectionPayment): bool
    {
        // Force delete is not allowed for financial records
        $this->logUnauthorizedAccess($user, 'forceDelete', $collectionPayment);

        return false;
    }

    /**
     * Determine whether the user can mark a payment as collected.
     */
    public function collect(User $user, CollectionPayment $collectionPayment): bool
    {
        // Admins and employees can collect payments
        if ($this->isAdmin($user) || $this->isEmployee($user)) {
            return true;
        }

        $this->logUnauthorizedAccess($user, 'collect', $collectionPayment);

        return false;
    }
}

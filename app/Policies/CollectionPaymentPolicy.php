<?php

namespace App\Policies;

use App\Models\CollectionPayment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CollectionPaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // السماح بعرض القائمة
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CollectionPayment $collectionPayment): bool
    {
        return true; // السماح بعرض التفاصيل
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // السماح بالإنشاء
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CollectionPayment $collectionPayment): bool
    {
        return true; // السماح بالتعديل
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CollectionPayment $collectionPayment): bool
    {
        return false; // منع الحذف نهائياً
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CollectionPayment $collectionPayment): bool
    {
        return false; // منع الاسترجاع
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CollectionPayment $collectionPayment): bool
    {
        return false; // منع الحذف النهائي
    }
}

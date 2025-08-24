<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\User;

class UserPermissions
{
    /**
     * Check if user can manage users
     */
    public static function canManageUsers(User $user): bool
    {
        return in_array($user->type, [
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
        ]);
    }
    
    /**
     * Check if user can manage employees
     */
    public static function canManageEmployees(User $user): bool
    {
        return in_array($user->type, [
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
        ]);
    }
    
    /**
     * Check if user can manage owners
     */
    public static function canManageOwners(User $user): bool
    {
        return in_array($user->type, [
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::EMPLOYEE->value,
        ]);
    }
    
    /**
     * Check if user can manage tenants
     */
    public static function canManageTenants(User $user): bool
    {
        return in_array($user->type, [
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::EMPLOYEE->value,
        ]);
    }
    
    /**
     * Check if user can manage properties
     */
    public static function canManageProperties(User $user): bool
    {
        return in_array($user->type, [
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::EMPLOYEE->value,
        ]);
    }
    
    /**
     * Check if user can manage financial data
     */
    public static function canManageFinancials(User $user): bool
    {
        return in_array($user->type, [
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::EMPLOYEE->value,
        ]);
    }
    
    /**
     * Check if user can access system administration
     */
    public static function canAccessSystemAdmin(User $user): bool
    {
        return $user->type === UserType::SUPER_ADMIN->value;
    }
    
    /**
     * Check if user can access debug tools
     */
    public static function canAccessDebugTools(User $user): bool
    {
        return $user->type === UserType::SUPER_ADMIN->value;
    }
    
    /**
     * Check if user can view all data
     */
    public static function canViewAllData(User $user): bool
    {
        return in_array($user->type, [
            UserType::SUPER_ADMIN->value,
            UserType::ADMIN->value,
            UserType::EMPLOYEE->value,
        ]);
    }
    
    /**
     * Check if user should only see their own data
     */
    public static function isRestrictedToOwnData(User $user): bool
    {
        return in_array($user->type, [
            UserType::OWNER->value,
            UserType::TENANT->value,
        ]);
    }
    
    /**
     * Get query scope based on user type
     */
    public static function scopeByUserType($query, User $user, string $ownerField = 'user_id')
    {
        if (self::isRestrictedToOwnData($user)) {
            return $query->where($ownerField, $user->id);
        }
        return $query;
    }
    
    /**
     * Check if user can edit specific user type
     */
    public static function canEditUserType(User $currentUser, string $targetUserType): bool
    {
        // Super admin can edit anyone
        if ($currentUser->type === UserType::SUPER_ADMIN->value) {
            return true;
        }
        
        // Admin can edit everyone except super admin
        if ($currentUser->type === UserType::ADMIN->value) {
            return $targetUserType !== UserType::SUPER_ADMIN->value;
        }
        
        // Employee can edit owners and tenants only
        if ($currentUser->type === UserType::EMPLOYEE->value) {
            return in_array($targetUserType, [
                UserType::OWNER->value,
                UserType::TENANT->value,
            ]);
        }
        
        // Others can't edit users
        return false;
    }
    
    /**
     * Get allowed user types for creation
     */
    public static function getAllowedUserTypesForCreation(User $user): array
    {
        if ($user->type === UserType::SUPER_ADMIN->value) {
            return UserType::options();
        }
        
        if ($user->type === UserType::ADMIN->value) {
            // Can't create super admin
            $types = UserType::options();
            unset($types[UserType::SUPER_ADMIN->value]);
            return $types;
        }
        
        if ($user->type === UserType::EMPLOYEE->value) {
            // Can only create owners and tenants
            return [
                UserType::OWNER->value => UserType::OWNER->label(),
                UserType::TENANT->value => UserType::TENANT->label(),
            ];
        }
        
        return [];
    }
}
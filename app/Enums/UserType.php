<?php

namespace App\Enums;

enum UserType: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';
    case OWNER = 'owner';
    case TENANT = 'tenant';
    
    /**
     * Get the label for the user type
     */
    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'مدير النظام',
            self::ADMIN => 'مدير',
            self::EMPLOYEE => 'موظف',
            self::OWNER => 'مالك',
            self::TENANT => 'مستأجر',
        };
    }
    
    /**
     * Get the color for badges
     */
    public function color(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'danger',
            self::ADMIN => 'warning',
            self::EMPLOYEE => 'info',
            self::OWNER => 'success',
            self::TENANT => 'gray',
        };
    }
    
    /**
     * Check if user can access admin panel
     */
    public function canAccessPanel(): bool
    {
        return match($this) {
            self::SUPER_ADMIN, self::ADMIN, self::EMPLOYEE => true,
            self::OWNER, self::TENANT => false, // سيتم تفعيلها لاحقاً
        };
    }
    
    /**
     * Get all values as array for select options
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }
        return $options;
    }
    
    /**
     * Get employee types only
     */
    public static function employeeTypes(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::EMPLOYEE,
        ];
    }
    
    /**
     * Get client types only
     */
    public static function clientTypes(): array
    {
        return [
            self::OWNER,
            self::TENANT,
        ];
    }
}
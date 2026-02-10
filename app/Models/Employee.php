<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends User
{
    use HasFactory;

    protected $table = 'users';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope to filter by employee types
        static::addGlobalScope('employee', function (Builder $builder) {
            $builder->whereIn('type', [
                UserType::EMPLOYEE->value,
                UserType::ADMIN->value,
                UserType::SUPER_ADMIN->value,
            ]);
        });

        // Auto-set type on creation
        static::creating(function ($employee) {
            // Only set type to EMPLOYEE if it's not already an admin type
            if (! $employee->type || ! in_array($employee->type, [
                UserType::ADMIN->value,
                UserType::SUPER_ADMIN->value,
            ])) {
                $employee->type = UserType::EMPLOYEE->value;
            }
        });
    }

    /**
     * Get the properties managed by this employee.
     */
    public function managedProperties()
    {
        return $this->hasMany(Property::class, 'manager_id');
    }
}

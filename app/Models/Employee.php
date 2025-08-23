<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\UserType;

class Employee extends User
{
    use HasFactory, SoftDeletes;

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

        // Auto-set type on creation if not set
        static::creating(function ($employee) {
            if (!$employee->type) {
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

    /**
     * Get tasks assigned to this employee.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

}
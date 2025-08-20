<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class Employee extends User
{
    use HasFactory, SoftDeletes;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone1',
        'phone2',
        'identity_file',
        'user_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope to filter by employee role
        static::addGlobalScope('employee', function (Builder $builder) {
            $builder->whereHas('roles', function ($query) {
                $query->where('name', 'employee');
            });
        });

        // Auto-assign employee role on creation
        static::creating(function ($employee) {
            // This will be handled in the observer or after create
        });

        static::created(function ($employee) {
            $employeeRole = Role::firstOrCreate(
                ['name' => 'employee', 'guard_name' => 'web']
            );
            if ($employeeRole && !$employee->hasRole('employee')) {
                $employee->assignRole($employeeRole);
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
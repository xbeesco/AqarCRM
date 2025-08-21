<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class Owner extends User
{
    use HasFactory, SoftDeletes;

    protected $table = 'users';


    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope to filter by owner role
        static::addGlobalScope('owner', function (Builder $builder) {
            $builder->whereHas('roles', function ($query) {
                $query->where('name', 'owner');
            });
        });

        // Auto-assign owner role and set user_type on creation
        static::creating(function ($owner) {
            $owner->user_type = 'owner';
            // Auto-generate email and password from phone
            if ($owner->phone && !$owner->email) {
                $owner->email = $owner->phone . '@towntop.sa';
            }
            if ($owner->phone && !$owner->password) {
                $owner->password = bcrypt($owner->phone);
            }
        });

        static::created(function ($owner) {
            $ownerRole = Role::firstOrCreate(
                ['name' => 'owner', 'guard_name' => 'web']
            );
            if ($ownerRole && !$owner->hasRole('owner')) {
                $owner->assignRole($ownerRole);
            }
        });
    }

    /**
     * Get the properties owned by this owner.
     */
    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    /**
     * Get rental contracts for this owner's properties.
     */
    public function rentalContracts()
    {
        return $this->hasManyThrough(RentalContract::class, Property::class, 'owner_id', 'property_id');
    }

    /**
     * Get payments received by this owner.
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, RentalContract::class, 'owner_id', 'contract_id')
                    ->through('properties');
    }

    /**
     * Get maintenance requests for this owner's properties.
     */
    public function maintenanceRequests()
    {
        return $this->hasManyThrough(MaintenanceRequest::class, Property::class, 'owner_id', 'property_id');
    }

    /**
     * Get financial statements for this owner.
     */
    public function financialStatements()
    {
        return $this->hasMany(FinancialStatement::class, 'owner_id');
    }

    /**
     * Get property valuations for this owner's properties.
     */
    public function propertyValuations()
    {
        return $this->hasManyThrough(PropertyValuation::class, Property::class, 'owner_id', 'property_id');
    }

    /**
     * Get all units for this owner's properties.
     */
    public function units()
    {
        return $this->hasManyThrough(Unit::class, Property::class, 'owner_id', 'property_id');
    }

    /**
     * Get total active properties count.
     */
    public function getActivePropertiesCountAttribute()
    {
        return $this->properties()->where('status', 'active')->count();
    }

    /**
     * Get total rental income.
     */
    public function getTotalRentalIncomeAttribute()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    /**
     * Get currently vacant properties.
     */
    public function getVacantPropertiesAttribute()
    {
        return $this->properties()->where('status', 'vacant')->get();
    }
}
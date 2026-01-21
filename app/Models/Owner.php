<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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

        // Add global scope to filter by owner type
        static::addGlobalScope('owner', function (Builder $builder) {
            $builder->where('type', UserType::OWNER->value);
        });

        // Auto-set type on creation
        static::creating(function ($owner) {
            $owner->type = UserType::OWNER->value;
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
     * Get the supply payments for this owner.
     * Note: SupplyPayment model needs to be created
     */
    public function supplyPayments()
    {
        // return $this->hasMany(SupplyPayment::class, 'owner_id');
        return $this->hasMany(\App\Models\SupplyPayment::class, 'owner_id');
    }

    /**
     * Get rental contracts for this owner's properties.
     * Note: RentalContract model needs to be created
     */
    // public function rentalContracts()
    // {
    //     // return $this->hasManyThrough(RentalContract::class, Property::class, 'owner_id', 'property_id');
    //     return $this->hasManyThrough(\App\Models\RentalContract::class, Property::class, 'owner_id', 'property_id');
    // }

    /**
     * Get payments received by this owner.
     * Overrides the parent payments() method to return supplyPayments
     */
    public function payments()
    {
        return $this->supplyPayments();
    }

    /**
     * Get maintenance requests for this owner's properties.
     */
    // public function maintenanceRequests()
    // {
    //     return $this->hasManyThrough(MaintenanceRequest::class, Property::class, 'owner_id', 'property_id');
    // }

    /**
     * Get financial statements for this owner.
     */
    // public function financialStatements()
    // {
    //     return $this->hasMany(FinancialStatement::class, 'owner_id');
    // }

    /**
     * Get property valuations for this owner's properties.
     */
    // public function propertyValuations()
    // {
    //     return $this->hasManyThrough(PropertyValuation::class, Property::class, 'owner_id', 'property_id');
    // }

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

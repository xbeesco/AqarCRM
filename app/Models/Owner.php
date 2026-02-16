<?php

namespace App\Models;

use App\Enums\UserType;
use App\Services\OwnerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Owner extends User
{
    use HasFactory;

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
     */
    public function supplyPayments()
    {
        return $this->hasMany(SupplyPayment::class, 'owner_id');
    }

    /**
     * Get payments received by this owner.
     * Alias for supplyPayments for compatibility
     */
    public function payments()
    {
        return $this->supplyPayments();
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
    public function getActivePropertiesCountAttribute(): int
    {
        return app(OwnerService::class)->getActivePropertiesCount($this);
    }

    /**
     * Get total rental income (net supplied amounts).
     */
    public function getTotalRentalIncomeAttribute(): float
    {
        return app(OwnerService::class)->calculateTotalRentalIncome($this);
    }

    /**
     * Get currently vacant properties.
     */
    public function getVacantPropertiesAttribute()
    {
        return app(OwnerService::class)->getVacantProperties($this);
    }

    /**
     * Get the occupancy rate.
     */
    public function getOccupancyRateAttribute(): float
    {
        return app(OwnerService::class)->calculateOccupancyRate($this);
    }

    /**
     * Get total deducted commissions.
     */
    public function getTotalCommissionsAttribute(): float
    {
        return app(OwnerService::class)->calculateTotalCommissions($this);
    }
}

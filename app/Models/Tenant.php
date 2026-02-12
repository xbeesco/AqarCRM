<?php

namespace App\Models;

use App\Enums\UserType;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends User
{
    use HasFactory;

    protected $table = 'users';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope to filter by tenant type
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('type', UserType::TENANT->value);
        });

        // Auto-set type on creation
        static::creating(function ($tenant) {
            $tenant->type = UserType::TENANT->value;
        });
    }

    /**
     * Get the current property rented by this tenant.
     */
    public function currentProperty()
    {
        return $this->belongsTo(Property::class, 'current_property_id');
    }

    /**
     * Get the current rental contract for this tenant.
     * Note: UnitContract model needs to be created
     */
    public function currentContract()
    {
        // return $this->hasOne(UnitContract::class, 'tenant_id')
        return $this->hasOne(UnitContract::class, 'tenant_id')
            ->where('contract_status', 'active')
            ->latest();
    }

    /**
     * Get all rental contracts for this tenant.
     * Note: UnitContract model needs to be created
     */
    public function rentalContracts()
    {
        // return $this->hasMany(UnitContract::class, 'tenant_id');
        return $this->hasMany(UnitContract::class, 'tenant_id');
    }

    /**
     * Alias for unit contracts (compatibility)
     * Note: UnitContract model needs to be created
     */
    public function unitContracts()
    {
        // return $this->hasMany(UnitContract::class, 'tenant_id');
        return $this->hasMany(UnitContract::class, 'tenant_id');
    }

    /**
     * Get the active (current) unit contract for this tenant.
     */
    public function activeContract()
    {
        return $this->hasOne(UnitContract::class, 'tenant_id')
            ->where('contract_status', 'active')
            ->latest('start_date');
    }

    /**
     * Get collection payments for this tenant.
     */
    public function collectionPayments()
    {
        return $this->hasMany(CollectionPayment::class, 'tenant_id');
    }

    /**
     * Alias for collectionPayments (payment history).
     * Used in TenantResource for statistics and reports.
     */
    public function paymentHistory()
    {
        return $this->collectionPayments();
    }

    /**
     * Get complaints filed by this tenant.
     */
    // public function complaints()
    // {
    //     return $this->hasMany(Complaint::class, 'tenant_id');
    // }

    /**
     * Get maintenance requests by this tenant.
     */
    // public function maintenanceRequests()
    // {
    //     return $this->hasMany(MaintenanceRequest::class, 'tenant_id');
    // }

    /**
     * Get lease renewals for this tenant.
     */
    // public function leaseRenewals()
    // {
    //     return $this->hasMany(LeaseRenewal::class, 'tenant_id');
    // }

    /**
     * Get tenant evaluations/reviews.
     */
    // public function evaluations()
    // {
    //     return $this->hasMany(TenantEvaluation::class, 'tenant_id');
    // }

    /**
     * Get utility bills for this tenant.
     */
    // public function utilityBills()
    // {
    //     return $this->hasMany(UtilityBill::class, 'tenant_id');
    // }

    // ==========================================
    // Accessors (computed attributes)
    // ==========================================

    /**
     * Get total amount paid by tenant.
     */
    public function getTotalAmountPaidAttribute()
    {
        return app(TenantService::class)->calculateTotalPaid($this);
    }

    /**
     * Get outstanding balance for tenant.
     */
    public function getOutstandingBalanceAttribute()
    {
        return app(TenantService::class)->calculateOutstandingBalance($this);
    }

    /**
     * Check if tenant is in good standing.
     */
    public function getIsInGoodStandingAttribute(): bool
    {
        return app(TenantService::class)->isInGoodStanding($this);
    }

    /**
     * Check if tenant has an active contract.
     */
    public function getHasActiveContractAttribute(): bool
    {
        return app(TenantService::class)->hasActiveContract($this);
    }

    /**
     * Get tenant rating.
     */
    public function getTenantRatingAttribute(): array
    {
        return app(TenantService::class)->getTenantRating($this);
    }

    // ==========================================
    // Simple Check Methods
    // ==========================================

    /**
     * Check if tenant has active contract.
     */
    public function hasActiveContract(): bool
    {
        return $this->has_active_contract;
    }
}

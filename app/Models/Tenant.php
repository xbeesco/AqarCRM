<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends User
{
    use HasFactory, SoftDeletes;

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
        return $this->hasOne(\App\Models\UnitContract::class, 'tenant_id')
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
        return $this->hasMany(\App\Models\UnitContract::class, 'tenant_id');
    }

    /**
     * Alias for unit contracts (compatibility)
     * Note: UnitContract model needs to be created
     */
    public function unitContracts()
    {
        // return $this->hasMany(UnitContract::class, 'tenant_id');
        return $this->hasMany(\App\Models\UnitContract::class, 'tenant_id');
    }

    /**
     * Get payment history for this tenant.
     * Note: CollectionPayment model needs to be created
     */
    public function paymentHistory()
    {
        // return $this->hasMany(CollectionPayment::class, 'tenant_id')->orderBy('due_date_start', 'desc');
        return $this->hasMany(\App\Models\CollectionPayment::class, 'tenant_id')->orderBy('due_date_start', 'desc');
    }

    /**
     * Alias for collection payments
     * Note: CollectionPayment model needs to be created
     */
    public function payments()
    {
        // return $this->hasMany(CollectionPayment::class, 'tenant_id');
        return $this->hasMany(\App\Models\CollectionPayment::class, 'tenant_id');
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

    /**
     * Check if tenant has active contract.
     */
    public function hasActiveContract()
    {
        return $this->currentContract()->exists();
    }

    /**
     * Get total amount paid by tenant.
     */
    public function getTotalAmountPaidAttribute()
    {
        return $this->paymentHistory()->collectedPayments()->sum('total_amount');
    }

    /**
     * Get outstanding balance for tenant.
     */
    public function getOutstandingBalanceAttribute()
    {
        return $this->paymentHistory()
            ->byStatuses(['due', 'overdue'])
            ->sum('total_amount');
    }

    /**
     * Check if tenant is in good standing.
     */
    public function isInGoodStanding()
    {
        $outstandingPayments = $this->paymentHistory()
            ->overduePayments()
            ->count();

        return $outstandingPayments === 0;
    }
}

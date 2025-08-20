<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class Tenant extends User
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

        // Add global scope to filter by tenant role
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->whereHas('roles', function ($query) {
                $query->where('name', 'tenant');
            });
        });

        // Auto-assign tenant role on creation
        static::created(function ($tenant) {
            $tenantRole = Role::firstOrCreate(
                ['name' => 'tenant', 'guard_name' => 'web']
            );
            if ($tenantRole && !$tenant->hasRole('tenant')) {
                $tenant->assignRole($tenantRole);
            }
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
     */
    public function currentContract()
    {
        return $this->hasOne(RentalContract::class, 'tenant_id')
                    ->where('status', 'active')
                    ->latest();
    }

    /**
     * Get all rental contracts for this tenant.
     */
    public function rentalContracts()
    {
        return $this->hasMany(RentalContract::class, 'tenant_id');
    }

    /**
     * Get payment history for this tenant.
     */
    public function paymentHistory()
    {
        return $this->hasMany(Payment::class, 'tenant_id')->orderBy('payment_date', 'desc');
    }

    /**
     * Get complaints filed by this tenant.
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'tenant_id');
    }

    /**
     * Get maintenance requests by this tenant.
     */
    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'tenant_id');
    }

    /**
     * Get lease renewals for this tenant.
     */
    public function leaseRenewals()
    {
        return $this->hasMany(LeaseRenewal::class, 'tenant_id');
    }

    /**
     * Get tenant evaluations/reviews.
     */
    public function evaluations()
    {
        return $this->hasMany(TenantEvaluation::class, 'tenant_id');
    }

    /**
     * Get utility bills for this tenant.
     */
    public function utilityBills()
    {
        return $this->hasMany(UtilityBill::class, 'tenant_id');
    }

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
        return $this->paymentHistory()->where('status', 'completed')->sum('amount');
    }

    /**
     * Get outstanding balance for tenant.
     */
    public function getOutstandingBalanceAttribute()
    {
        return $this->paymentHistory()->where('status', 'pending')->sum('amount');
    }

    /**
     * Check if tenant is in good standing.
     */
    public function isInGoodStanding()
    {
        $outstandingPayments = $this->paymentHistory()
                                   ->where('status', 'overdue')
                                   ->where('due_date', '<', now())
                                   ->count();

        return $outstandingPayments === 0;
    }
}
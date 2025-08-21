<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UnitContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'unit_id',
        'property_id',
        'tenant_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'security_deposit',
        'utilities_included',
        'payment_method',
        'grace_period',
        'late_fee_rate',
        'evacuation_notice',
        'status',
        'special_terms',
        'notes',
        'terminated_at',
        'termination_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'terminated_at' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'late_fee_rate' => 'decimal:2',
        'utilities_included' => 'boolean',
        'grace_period' => 'integer',
        'evacuation_notice' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = static::generateContractNumber();
            }
        });
    }

    /**
     * Generate unique contract number with format UC-{year}-{sequential}.
     */
    public static function generateContractNumber(): string
    {
        $year = date('Y');
        $prefix = "UC-{$year}-";
        
        $lastContract = static::where('contract_number', 'like', $prefix . '%')
            ->orderBy('contract_number', 'desc')
            ->first();

        if ($lastContract) {
            $lastNumber = (int) substr($lastContract->contract_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get remaining days attribute.
     */
    public function getRemainingDaysAttribute(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    /**
     * Calculate late fees for overdue payments.
     */
    public function calculateLateFees(int $daysLate): float
    {
        if ($daysLate <= $this->grace_period) {
            return 0;
        }

        $actualDaysLate = $daysLate - $this->grace_period;
        return round($this->monthly_rent * ($this->late_fee_rate / 100) * ($actualDaysLate / 30), 2);
    }

    /**
     * Get total contract value.
     */
    public function getTotalContractValue(): float
    {
        $months = $this->start_date->diffInMonths($this->end_date);
        return $this->monthly_rent * $months;
    }

    /**
     * Calculate early termination penalty.
     */
    public function calculateEarlyTerminationPenalty(): float
    {
        $remainingMonths = $this->end_date->diffInMonths(now(), false);
        
        if ($remainingMonths <= 0) {
            return 0;
        }

        // Penalty is typically 2 months rent
        return $this->monthly_rent * 2;
    }

    /**
     * Check if contract can be terminated early.
     */
    public function canTerminateEarly(): bool
    {
        return $this->status === 'active' && 
               $this->end_date > now();
    }

    /**
     * Check if contract is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->start_date <= now() &&
               $this->end_date >= now();
    }

    /**
     * Check if contract can be renewed.
     */
    public function canRenew(): bool
    {
        return $this->status === 'active' &&
               $this->end_date->diffInDays(now()) <= $this->evacuation_notice;
    }

    /**
     * Get status badge color attribute.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'active' => 'success',
            'expired' => 'warning',
            'terminated' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Relationship: Contract belongs to tenant (user).
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /**
     * Relationship: Contract belongs to unit.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Relationship: Contract belongs to property.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }


    /**
     * Scope: Get active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Scope: Get contracts expiring within specified days.
     */
    public function scopeExpiring($query, int $days)
    {
        return $query->where('status', 'active')
                    ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope: Get contracts for specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Get contracts for specific property.
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    /**
     * Scope: Get contracts for specific unit.
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PropertyContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'owner_id',
        'property_id',
        'commission_rate',
        'duration_months',
        'start_date',
        'end_date',
        'contract_status',
        'notary_number',
        'payment_day',
        'auto_renew',
        'notice_period_days',
        'terms_and_conditions',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'terminated_reason',
        'terminated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'commission_rate' => 'decimal:2',
        'auto_renew' => 'boolean',
        'approved_at' => 'datetime',
        'terminated_at' => 'datetime',
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
            
            // Auto-calculate end date
            if ($contract->start_date && $contract->duration_months) {
                $contract->end_date = Carbon::parse($contract->start_date)
                    ->addMonths($contract->duration_months)
                    ->subDay();
            }
        });

        static::updating(function ($contract) {
            // Recalculate end date if start date or duration changes
            if ($contract->isDirty(['start_date', 'duration_months'])) {
                $contract->end_date = Carbon::parse($contract->start_date)
                    ->addMonths($contract->duration_months)
                    ->subDay();
            }
        });
    }

    /**
     * Generate unique contract number with format PC-{year}-{sequential}.
     */
    public static function generateContractNumber(): string
    {
        $year = date('Y');
        $prefix = "PC-{$year}-";
        
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
     * Calculate commission amount based on rental payments.
     */
    public function calculateCommission(float $amount): float
    {
        return round($amount * ($this->commission_rate / 100), 2);
    }

    /**
     * Check if contract is currently active.
     */
    public function isActive(): bool
    {
        return $this->contract_status === 'active' &&
               $this->start_date <= now() &&
               $this->end_date >= now();
    }

    /**
     * Check if contract can be renewed.
     */
    public function canRenew(): bool
    {
        return $this->contract_status === 'active' &&
               $this->end_date->diffInDays(now()) <= $this->notice_period_days;
    }

    /**
     * Relationship: Contract belongs to owner (user).
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Relationship: Contract belongs to property.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relationship: Contract created by user.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Contract approved by user.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: Get active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('contract_status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Scope: Get contracts expiring within specified days.
     */
    public function scopeExpiring($query, int $days)
    {
        return $query->where('contract_status', 'active')
                    ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope: Get contracts for specific owner.
     */
    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Scope: Get contracts for specific property.
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
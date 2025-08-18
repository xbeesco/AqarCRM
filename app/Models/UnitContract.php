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
        'tenant_id',
        'unit_id',
        'property_id',
        'monthly_rent',
        'security_deposit',
        'duration_months',
        'start_date',
        'end_date',
        'contract_status',
        'payment_frequency',
        'payment_method',
        'grace_period_days',
        'late_fee_rate',
        'utilities_included',
        'furnished',
        'evacuation_notice_days',
        'terms_and_conditions',
        'special_conditions',
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
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'late_fee_rate' => 'decimal:2',
        'utilities_included' => 'boolean',
        'furnished' => 'boolean',
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
     * Generate payment schedule based on contract terms.
     */
    public function generatePaymentSchedule(): array
    {
        $payments = [];
        $frequencyMap = [
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'annually' => 12,
        ];

        $intervalMonths = $frequencyMap[$this->payment_frequency];
        $paymentAmount = $this->monthly_rent * $intervalMonths;
        $numberOfPayments = $this->duration_months / $intervalMonths;

        for ($i = 0; $i < $numberOfPayments; $i++) {
            $dueDate = Carbon::parse($this->start_date)->addMonths($i * $intervalMonths);
            $payments[] = [
                'due_date' => $dueDate,
                'amount' => $paymentAmount,
                'period_start' => $dueDate->copy(),
                'period_end' => $dueDate->copy()->addMonths($intervalMonths)->subDay(),
            ];
        }

        return $payments;
    }

    /**
     * Calculate late fees for overdue payments.
     */
    public function calculateLateFee(float $amount, int $daysLate): float
    {
        if ($daysLate <= $this->grace_period_days) {
            return 0;
        }

        $actualDaysLate = $daysLate - $this->grace_period_days;
        return round($amount * ($this->late_fee_rate / 100) * ($actualDaysLate / 30), 2);
    }

    /**
     * Get total contract value.
     */
    public function getTotalContractValue(): float
    {
        return $this->monthly_rent * $this->duration_months;
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
        return $this->contract_status === 'active' && 
               $this->end_date > now();
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
               $this->end_date->diffInDays(now()) <= $this->evacuation_notice_days;
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
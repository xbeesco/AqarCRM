<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'duration_months' => 'integer',
        'grace_period_days' => 'integer',
        'evacuation_notice_days' => 'integer',
        'late_fee_rate' => 'decimal:2',
        'utilities_included' => 'boolean',
        'furnished' => 'boolean',
        'approved_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    /**
     * Get the end date calculated from start_date and duration_months.
     */
    public function getEndDateAttribute()
    {
        if (!$this->start_date || !$this->duration_months) {
            return null;
        }
        return $this->start_date->copy()->addMonths($this->duration_months);
    }

    /**
     * Get total contract value.
     */
    public function getTotalContractValue(): float
    {
        return $this->monthly_rent * $this->duration_months;
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
}
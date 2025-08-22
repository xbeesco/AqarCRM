<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'unit_id',
        'tenant_id',
        'contract_date',
        'duration_months',
        'payment_frequency',
        'monthly_rent',
        'contract_file',
        'notes',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'duration_months' => 'integer',
    ];

    /**
     * Get the end date calculated from contract_date and duration_months.
     */
    public function getEndDateAttribute()
    {
        if (!$this->contract_date || !$this->duration_months) {
            return null;
        }
        return $this->contract_date->copy()->addMonths($this->duration_months);
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
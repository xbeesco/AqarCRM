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
        'late_fee_rate' => 'decimal:2',
        'utilities_included' => 'boolean',
        'furnished' => 'boolean',
        'evacuation_notice_days' => 'integer',
        'approved_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($contract) {
            // Generate contract number if not set
            if (empty($contract->contract_number)) {
                $year = date('Y');
                $lastContract = static::whereYear('created_at', $year)
                    ->orderBy('id', 'desc')
                    ->first();
                
                $number = $lastContract ? intval(substr($lastContract->contract_number, -4)) + 1 : 1;
                $contract->contract_number = sprintf('UC-%s-%04d', $year, $number);
            }
            
            // Calculate end_date if not set
            if (empty($contract->end_date) && $contract->start_date && $contract->duration_months) {
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }
            
            // Set default values if not provided
            if (is_null($contract->security_deposit)) {
                $contract->security_deposit = $contract->monthly_rent ?? 0;
            }
            
            if (is_null($contract->grace_period_days)) {
                $contract->grace_period_days = 5;
            }
            
            if (is_null($contract->late_fee_rate)) {
                $contract->late_fee_rate = 5.00;
            }
            
            if (is_null($contract->evacuation_notice_days)) {
                $contract->evacuation_notice_days = 30;
            }
            
            if (is_null($contract->contract_status)) {
                $contract->contract_status = 'active';
            }
        });
        
        static::updating(function ($contract) {
            // Recalculate end_date if start_date or duration_months changed
            if ($contract->isDirty(['start_date', 'duration_months']) && $contract->start_date && $contract->duration_months) {
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }
        });
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
    
    /**
     * Relationship: Contract has many payments.
     */
    public function payments()
    {
        return $this->hasMany(CollectionPayment::class, 'unit_contract_id');
    }
    
// تم إزالة canGenerateCollectionPayments لأن التوليد يتم تلقائياً دائماً
}
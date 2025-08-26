<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'payment_frequency',
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
        'duration_months' => 'integer',
        'payment_day' => 'integer',
        'auto_renew' => 'boolean',
        'notice_period_days' => 'integer',
        'approved_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $contract->contract_number = sprintf('PC-%s-%04d', $year, $count);
            }
            
            // Set owner_id from property if not set
            if (empty($contract->owner_id) && $contract->property_id) {
                $property = Property::find($contract->property_id);
                if ($property) {
                    $contract->owner_id = $property->owner_id;
                }
            }
            
            // Calculate end_date if not set
            if (empty($contract->end_date) && $contract->start_date && $contract->duration_months) {
                $startDate = \Carbon\Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
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
     * Get owner attribute from property relationship.
     */
    public function getOwnerAttribute()
    {
        return $this->property?->owner;
    }

    /**
     * Calculate payments count based on duration and frequency.
     */
    public function getPaymentsCountAttribute()
    {
        // Return stored value if exists, otherwise calculate
        if (isset($this->attributes['payments_count']) && !is_null($this->attributes['payments_count'])) {
            return $this->attributes['payments_count'];
        }

        return \App\Services\PropertyContractService::calculatePaymentsCount(
            $this->duration_months ?? 0,
            $this->payment_frequency ?? 'monthly'
        );
    }

    /**
     * Relationship: Contract belongs to property.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Relationship: Contract belongs to owner.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
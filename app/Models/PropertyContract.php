<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'contract_date',
        'duration_months',
        'commission_rate',
        'payment_frequency',
        'payments_count',
        'contract_file',
        'notes',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'commission_rate' => 'decimal:2',
        'duration_months' => 'integer',
        'payment_frequency' => 'string',
        'payments_count' => 'integer',
    ];

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
        if (!is_null($this->attributes['payments_count'])) {
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
}
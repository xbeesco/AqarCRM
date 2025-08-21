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
        'property_id',
        'owner_id',
        'start_date',
        'end_date',
        'commission_rate',
        'notary_number',
        'payment_day',
        'auto_renew',
        'notice_period',
        'status',
        'terms',
        'notes',
        'terminated_at',
        'termination_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'terminated_at' => 'date',
        'commission_rate' => 'decimal:2',
        'auto_renew' => 'boolean',
        'payment_day' => 'integer',
        'notice_period' => 'integer',
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
               $this->end_date->diffInDays(now()) <= $this->notice_period;
    }
    
    /**
     * Check renewal eligibility.
     */
    public function checkRenewalEligibility(): bool
    {
        if (!$this->auto_renew) {
            return false;
        }

        if ($this->status !== 'active') {
            return false;
        }

        $daysUntilExpiry = now()->diffInDays($this->end_date, false);
        
        return $daysUntilExpiry <= $this->notice_period && $daysUntilExpiry > 0;
    }

    /**
     * Check if can transition to new status.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            'draft' => ['active', 'terminated'],
            'active' => ['suspended', 'expired', 'terminated'],
            'suspended' => ['active', 'terminated'],
            'expired' => ['terminated'],
            'terminated' => [],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? []);
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
     * Get status badge color attribute.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'active' => 'success',
            'suspended' => 'warning',
            'expired' => 'danger',
            'terminated' => 'danger',
            default => 'gray',
        };
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
<?php

namespace App\Models;

use App\Services\PropertyContractService;
use App\Services\UnitContractService;
use Carbon\Carbon;
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
        'notes',
        'file',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'duration_months' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            // Generate contract number if not set
            if (empty($contract->contract_number)) {
                // Use millisecond timestamp to ensure uniqueness
                $contract->contract_number = 'UC-'.round(microtime(true) * 1000);
            }

            // Calculate end_date if not set
            if (empty($contract->end_date) && $contract->start_date && $contract->duration_months) {
                $startDate = Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }
        });

        static::updating(function ($contract) {
            // Recalculate end_date if start_date or duration_months changed
            if ($contract->isDirty(['start_date', 'duration_months']) && $contract->start_date && $contract->duration_months) {
                $startDate = Carbon::parse($contract->start_date);
                $contract->end_date = $startDate->copy()->addMonths($contract->duration_months)->subDay();
            }
        });
    }

    // ==========================================
    // Relationships
    // ==========================================

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
     * Relationship: Contract has many collection payments.
     */
    public function collectionPayments()
    {
        return $this->hasMany(CollectionPayment::class, 'unit_contract_id');
    }

    /**
     * Relationship: Contract has many payments (alias for collectionPayments).
     */
    public function payments()
    {
        return $this->hasMany(CollectionPayment::class, 'unit_contract_id');
    }

    /**
     * Scope: Active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('contract_status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope: Draft contracts.
     */
    public function scopeDraft($query)
    {
        return $query->where('contract_status', 'draft');
    }

    /**
     * Scope: Expired contracts.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('contract_status', 'expired')
                ->orWhere(function ($q2) {
                    $q2->where('contract_status', 'active')
                        ->where('end_date', '<', now());
                });
        });
    }

    /**
     * Scope: Terminated contracts.
     */
    public function scopeTerminated($query)
    {
        return $query->where('contract_status', 'terminated');
    }

    /**
     * Scope: Renewed contracts.
     */
    public function scopeRenewed($query)
    {
        return $query->where('contract_status', 'renewed');
    }

    /**
     * Scope: Contracts expiring soon (within N days).
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('contract_status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    // ==========================================
    // Accessors (computed attributes)
    // ==========================================

    /**
     * Get payments count attribute dynamically.
     */
    public function getPaymentsCountAttribute()
    {
        return PropertyContractService::calculatePaymentsCount(
            $this->duration_months ?? 0,
            $this->payment_frequency ?? 'monthly'
        );
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->contract_status) {
            'draft' => 'gray',
            'active' => $this->end_date < now()->addDays(30) ? 'warning' : 'success',
            'expired' => 'danger',
            'terminated' => 'danger',
            'renewed' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Get status label in Arabic.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->contract_status) {
            'draft' => 'مسودة',
            'active' => 'نشط',
            'expired' => 'منتهي',
            'terminated' => 'ملغي',
            'renewed' => 'مُجدد',
            default => $this->contract_status
        };
    }

    /**
     * Get status label (method wrapper for accessor).
     */
    public function getStatusLabel(): string
    {
        return $this->status_label;
    }

    /**
     * Get status color (method wrapper for accessor).
     */
    public function getStatusColor(): string
    {
        return $this->status_color;
    }

    /**
     * Get remaining days attribute.
     */
    public function getRemainingDaysAttribute(): int
    {
        return app(UnitContractService::class)->getRemainingDays($this);
    }

    /**
     * Get remaining days (method wrapper for accessor).
     */
    public function getRemainingDays(): int
    {
        return $this->remaining_days;
    }

    /**
     * Check if payments can be generated.
     */
    public function getCanGeneratePaymentsAttribute(): bool
    {
        return app(UnitContractService::class)->canGeneratePayments($this);
    }

    /**
     * Method wrapper for canGeneratePayments (used by Observer)
     */
    public function canGeneratePayments(): bool
    {
        return $this->can_generate_payments;
    }

    /**
     * Check if contract can be rescheduled.
     */
    public function getCanRescheduleAttribute(): bool
    {
        return app(UnitContractService::class)->canReschedule($this);
    }

    /**
     * Method wrapper for canReschedule (used by Observer)
     */
    public function canReschedule(): bool
    {
        return $this->can_reschedule;
    }

    // ==========================================
    // Simple Check Methods
    // ==========================================

    /**
     * Check if contract is currently active.
     */
    public function isActive(): bool
    {
        return $this->contract_status === 'active'
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    /**
     * Check if contract has expired.
     */
    public function hasExpired(): bool
    {
        return $this->contract_status === 'expired'
            || ($this->contract_status === 'active' && $this->end_date < now());
    }

    /**
     * Check if contract is draft.
     */
    public function isDraft(): bool
    {
        return $this->contract_status === 'draft';
    }

    /**
     * Check if contract was terminated.
     */
    public function isTerminated(): bool
    {
        return $this->contract_status === 'terminated';
    }

    /**
     * Check if contract was renewed.
     */
    public function isRenewed(): bool
    {
        return $this->contract_status === 'renewed';
    }

    /**
     * Get unpaid payments for this contract.
     */
    public function getUnpaidPayments()
    {
        return $this->collectionPayments()->whereNull('paid_date')->get();
    }

    /**
     * Get count of unpaid payments.
     */
    public function getUnpaidPaymentsCount(): int
    {
        return $this->collectionPayments()->whereNull('paid_date')->count();
    }

    /**
     * Check if contract can be rescheduled.
     */
    public function canBeRescheduled(): bool
    {
        $validStatuses = ['active', 'draft'];
        if (! in_array($this->contract_status, $validStatuses)) {
            return false;
        }

        if (! $this->collectionPayments()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Get paid payments for this contract.
     */
    public function getPaidPayments()
    {
        return $this->collectionPayments()->whereNotNull('paid_date')->get();
    }

    /**
     * Get count of paid payments.
     */
    public function getPaidPaymentsCount(): int
    {
        return $this->collectionPayments()->whereNotNull('paid_date')->count();
    }

    /**
     * Get count of paid months (approximate).
     */
    public function getPaidMonthsCount(): int
    {
        $count = 0;
        foreach ($this->getPaidPayments() as $payment) {
            $monthsPerPayment = match ($this->payment_frequency) {
                'monthly' => 1,
                'quarterly' => 3,
                'semi_annually' => 6,
                'annually' => 12,
                default => 1,
            };
            $count += $monthsPerPayment;
        }

        return $count;
    }

    /**
     * Get remaining months (unpaid).
     */
    public function getRemainingMonths(): int
    {
        $totalMonths = $this->duration_months ?? 0;
        $paidMonths = $this->getPaidMonthsCount();

        return max(0, $totalMonths - $paidMonths);
    }
}

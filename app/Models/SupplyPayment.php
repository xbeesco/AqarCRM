<?php

namespace App\Models;

use App\Services\PaymentNumberGenerator;
use App\Services\SupplyPaymentService;
use App\Traits\HasPaymentNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyPayment extends Model
{
    use HasPaymentNumber, HasFactory;

    /**
     * Get the payment number prefix.
     */
    public static function getPaymentNumberPrefix(): string
    {
        return PaymentNumberGenerator::PREFIX_SUPPLY;
    }

    protected $fillable = [
        'payment_number',
        'property_contract_id',
        'owner_id',
        'gross_amount',
        'commission_amount',
        'commission_rate',
        'maintenance_deduction',
        'other_deductions',
        'net_amount',
        'due_date',
        'paid_date',
        'collected_by',
        'delay_duration',
        'delay_reason',
        'approval_status',
        'approved_by',
        'approved_at',
        'bank_transfer_reference',
        'invoice_details',
        'deduction_details',
        'month_year',
        'notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'maintenance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'approved_at' => 'datetime',
        'invoice_details' => 'array',
        'deduction_details' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            // Note: Payment number generation is handled by HasPaymentNumber trait

            $service = app(SupplyPaymentService::class);
            $payment->net_amount = $service->calculateNetAmount($payment);
        });

        static::updating(function ($payment) {
            $service = app(SupplyPaymentService::class);
            $payment->net_amount = $service->calculateNetAmount($payment);
        });
    }

    public function propertyContract(): BelongsTo
    {
        return $this->belongsTo(PropertyContract::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * Get dynamic supply status based on dates.
     */
    public function getSupplyStatusAttribute(): string
    {
        if ($this->paid_date) {
            return 'collected';
        }

        if ($this->due_date <= \Carbon\Carbon::now()) {
            return 'worth_collecting';
        }

        return 'pending';
    }

    /**
     * Get Arabic label for status.
     */
    public function getSupplyStatusLabelAttribute(): string
    {
        return match ($this->supply_status) {
            'pending' => 'قيد الانتظار',
            'worth_collecting' => 'تستحق التوريد',
            'collected' => 'تم التوريد',
            default => $this->supply_status,
        };
    }

    /**
     * Get badge color for status.
     */
    public function getSupplyStatusColorAttribute(): string
    {
        return match ($this->supply_status) {
            'pending' => 'warning',
            'worth_collecting' => 'info',
            'collected' => 'success',
            default => 'gray',
        };
    }

    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopePending($query)
    {
        return $query->whereNull('paid_date')
            ->where('due_date', '>', now());
    }

    public function scopeWorthCollecting($query)
    {
        return $query->whereNull('paid_date')
            ->where('due_date', '<=', now());
    }

    public function scopeCollected($query)
    {
        return $query->whereNotNull('paid_date');
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeByMonth($query, $monthYear)
    {
        return $query->where('month_year', $monthYear);
    }

    /**
     * Check if payment requires approval.
     */
    public function getRequiresApprovalAttribute(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if payment can be confirmed.
     */
    public function getCanBeConfirmedAttribute(): bool
    {
        $result = app(SupplyPaymentService::class)->canConfirmPayment($this);

        return $result['can_confirm'];
    }

    public function requiresApproval(): bool
    {
        return $this->requires_approval;
    }

    public function isCollected(): bool
    {
        return $this->paid_date !== null;
    }

    public function isWorthCollecting(): bool
    {
        return $this->supply_status === 'worth_collecting';
    }
}

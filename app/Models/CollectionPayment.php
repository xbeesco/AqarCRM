<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Services\CollectionPaymentService;
use App\Services\PaymentNumberGenerator;
use App\Traits\HasPaymentNumber;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionPayment extends Model
{
    use HasFactory;
    use HasPaymentNumber;

    /**
     * Get payment number prefix.
     */
    public static function getPaymentNumberPrefix(): string
    {
        return PaymentNumberGenerator::PREFIX_COLLECTION;
    }

    protected static function booted()
    {
        static::deleting(function ($payment) {
            return false;
        });
    }

    protected $fillable = [
        'payment_number',
        'unit_contract_id',
        'unit_id',
        'property_id',
        'tenant_id',
        'amount',
        'late_fee',
        'total_amount',
        'due_date_start',
        'due_date_end',
        'paid_date',
        'collection_date',
        'collected_by',
        'delay_duration',
        'delay_reason',
        'late_payment_notes',
        'payment_reference',
        'receipt_number',
        'month_year',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'due_date_start' => 'date',
        'due_date_end' => 'date',
        'paid_date' => 'date',
        'collection_date' => 'date',
        'delay_duration' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            // Note: Payment number generation is handled by HasPaymentNumber trait

            // Default late fee
            if (is_null($payment->late_fee)) {
                $payment->late_fee = 0;
            }

            // Calculate total
            $payment->total_amount = ($payment->amount ?? 0) + ($payment->late_fee ?? 0);

            // Generate month_year for reports
            if (empty($payment->month_year)) {
                $dateForMonth = $payment->due_date_start ?? Carbon::now();
                $payment->month_year = Carbon::parse($dateForMonth)->format('Y-m');
            }
        });

        static::updating(function ($payment) {
            // Recalculate total
            $payment->total_amount = ($payment->amount ?? 0) + ($payment->late_fee ?? 0);

            // Update month_year
            if (empty($payment->month_year) && ! empty($payment->due_date_start)) {
                $payment->month_year = Carbon::parse($payment->due_date_start)->format('Y-m');
            }
        });
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function unitContract(): BelongsTo
    {
        return $this->belongsTo(UnitContract::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // ==========================================
    // Accessors (computed attributes)
    // ==========================================

    /**
     * Get payment status dynamically.
     */
    public function getPaymentStatusAttribute(): PaymentStatus
    {
        if ($this->collection_date) {
            return PaymentStatus::COLLECTED;
        }

        if ($this->delay_duration && $this->delay_duration > 0) {
            return PaymentStatus::POSTPONED;
        }

        $today = Carbon::now()->startOfDay();
        $paymentsDueDays = Setting::get('payment_due_days', 7);
        $overdueDate = $today->copy()->subDays($paymentsDueDays);

        if ($this->due_date_start < $overdueDate) {
            return PaymentStatus::OVERDUE;
        }

        if ($this->due_date_start <= $today) {
            return PaymentStatus::DUE;
        }

        return PaymentStatus::UPCOMING;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->payment_status->label();
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return $this->payment_status->color();
    }

    public function getPaymentStatusIconAttribute(): string
    {
        return $this->payment_status->icon();
    }

    /**
     * Check if payment can be postponed.
     */
    public function getCanBePostponedAttribute(): bool
    {
        return app(CollectionPaymentService::class)->canBePostponed($this);
    }

    /**
     * Check if payment can be collected.
     */
    public function getCanBeCollectedAttribute(): bool
    {
        return app(CollectionPaymentService::class)->canBeCollected($this);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByMonth($query, $monthYear)
    {
        return $query->where('month_year', $monthYear);
    }

    /**
     * Scope for payments due for collection.
     */
    public function scopeDueForCollection($query)
    {
        $today = Carbon::now()->startOfDay();

        return $query->where('due_date_start', '<=', $today)
            ->whereNull('collection_date')
            ->where(function ($q) {
                $q->whereNull('delay_duration')
                    ->orWhere('delay_duration', 0);
            });
    }

    /**
     * Scope for postponed payments.
     */
    public function scopePostponedPayments($query)
    {
        $today = Carbon::now()->startOfDay();

        return $query->where('due_date_start', '<=', $today)
            ->whereNull('collection_date')
            ->whereNotNull('delay_duration')
            ->where('delay_duration', '>', 0);
    }

    /**
     * Scope for overdue payments.
     */
    public function scopeOverduePayments($query)
    {
        $paymentDueDays = Setting::get('payment_due_days', 7);
        $today = Carbon::now()->startOfDay();
        $overdueDate = $today->copy()->subDays($paymentDueDays);

        return $query->where('due_date_start', '<', $overdueDate)
            ->whereNull('collection_date')
            ->where(function ($q) {
                $q->whereNull('delay_duration')
                    ->orWhere('delay_duration', 0);
            });
    }

    /**
     * Scope for collected payments.
     */
    public function scopeCollectedPayments($query)
    {
        return $query->whereNotNull('collection_date');
    }

    /**
     * Scope for upcoming payments.
     */
    public function scopeUpcomingPayments($query)
    {
        $today = Carbon::now()->startOfDay();

        return $query->whereNull('collection_date')
            ->where('due_date_start', '>', $today);
    }

    /**
     * Scope to filter by specific status.
     */
    public function scopeByStatus($query, PaymentStatus $status)
    {
        return match ($status) {
            PaymentStatus::COLLECTED => $this->scopeCollectedPayments($query),
            PaymentStatus::POSTPONED => $this->scopePostponedPayments($query),
            PaymentStatus::OVERDUE => $this->scopeOverduePayments($query),
            PaymentStatus::DUE => $this->scopeDueForCollection($query),
            PaymentStatus::UPCOMING => $this->scopeUpcomingPayments($query),
        };
    }

    /**
     * Scope to filter by multiple statuses.
     */
    public function scopeByStatuses($query, array $statuses)
    {
        return $query->where(function ($q) use ($statuses) {
            foreach ($statuses as $status) {
                $statusEnum = $status instanceof PaymentStatus ? $status : PaymentStatus::from($status);
                $q->orWhere(function ($subQuery) use ($statusEnum) {
                    $this->scopeByStatus($subQuery, $statusEnum);
                });
            }
        });
    }

    public function scopePostponedWithDetails($query)
    {
        return $query->postponedPayments()
            ->with(['tenant:id,name,phone', 'unit:id,name', 'property:id,name'])
            ->select([
                'id', 'payment_number', 'tenant_id', 'unit_id', 'property_id',
                'amount', 'total_amount', 'delay_reason', 'delay_duration',
                'due_date_start', 'due_date_end', 'late_payment_notes', 'created_at',
            ]);
    }

    public function scopeCriticalPostponed($query)
    {
        return $query->postponedPayments()
            ->where(function ($q) {
                $q->where('delay_duration', '>', 30)
                    ->orWhere('due_date_end', '<', Carbon::now()->subDays(30));
            });
    }

    public function scopeRecentPostponed($query, $days = 7)
    {
        return $query->postponedPayments()
            ->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope for paid payments (alias).
     */
    public function scopePaid($query)
    {
        return $query->whereNotNull('collection_date');
    }

    /**
     * Scope for unpaid payments (alias).
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNull('collection_date');
    }

    /**
     * Check if payment is overdue.
     */
    public function isOverdue(): bool
    {
        return app(CollectionPaymentService::class)->isOverdue($this);
    }
}

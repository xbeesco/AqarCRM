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
            // توليد رقم الدفعة تلقائياً
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }

            // قيمة افتراضية للغرامة
            if (is_null($payment->late_fee)) {
                $payment->late_fee = 0;
            }

            // حساب المجموع الكلي
            $payment->total_amount = ($payment->amount ?? 0) + ($payment->late_fee ?? 0);

            // توليد الشهر والسنة للتقارير
            if (empty($payment->month_year)) {
                $dateForMonth = $payment->due_date_start ?? Carbon::now();
                $payment->month_year = Carbon::parse($dateForMonth)->format('Y-m');
            }
        });

        static::updating(function ($payment) {
            // Recalculate total
            $payment->total_amount = ($payment->amount ?? 0) + ($payment->late_fee ?? 0);

            // تحديث الشهر والسنة
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

        $today = Carbon::now()->startOfDay();

        // التحقق من التأجيل - لازم نتأكد إن فترة التأجيل لسه سارية
        if ($this->delay_duration && $this->delay_duration > 0) {
            $postponedUntil = Carbon::parse($this->due_date_start)->addDays($this->delay_duration);
            if ($today <= $postponedUntil) {
                return PaymentStatus::POSTPONED;
            }
            // فترة التأجيل انتهت - نكمل للحالات التانية
        }

        // حساب تاريخ الاستحقاق الفعلي (بعد التأجيل لو موجود)
        $effectiveDueDate = $this->due_date_start;
        if ($this->delay_duration && $this->delay_duration > 0) {
            $effectiveDueDate = Carbon::parse($this->due_date_start)->addDays($this->delay_duration);
        }

        $totalGraceDays = $this->getTotalGraceThreshold();
        $overdueDate = Carbon::parse($effectiveDueDate)->addDays($totalGraceDays);

        // إذا كانت متأخرة (تجاوزت مدة السماح الكلية)
        if ($today > $overdueDate) {
            return PaymentStatus::OVERDUE;
        }

        if ($effectiveDueDate <= $today) {
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
        return $this->collection_date === null &&
            ($this->delay_duration === null || $this->delay_duration == 0);
    }

    /**
     * Check if payment can be collected.
     */
    public function getCanBeCollectedAttribute(): bool
    {
        return $this->collection_date === null;
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

    public function scopePostponedWithDetails($query)
    {
        return $query->postponedPayments()
            ->with(['tenant:id,name,phone', 'unit:id,name', 'property:id,name'])
            ->select([
                'id',
                'payment_number',
                'tenant_id',
                'unit_id',
                'property_id',
                'amount',
                'total_amount',
                'delay_reason',
                'delay_duration',
                'due_date_start',
                'due_date_end',
                'late_payment_notes',
                'created_at',
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
     * Scope for payments due for collection (including expired postponements).
     */
    public function scopeDueForCollection($query)
    {
        $today = Carbon::now()->startOfDay()->toDateString();
        $totalGraceDays = (new self)->getTotalGraceThreshold();

        return $query->whereNull('collection_date')
            ->where(function ($q) use ($today, $totalGraceDays) {
                // دفعات بدون تأجيل ومستحقة (لم تتجاوز مدة السماح)
                $q->where(function ($sub) use ($today, $totalGraceDays) {
                    $sub->where(function ($s) {
                        $s->whereNull('delay_duration')->orWhere('delay_duration', 0);
                    })
                    ->where('due_date_start', '<=', $today)
                    ->whereRaw("DATE_ADD(due_date_start, INTERVAL ? DAY) >= ?", [$totalGraceDays, $today]);
                })
                // أو دفعات انتهى تأجيلها ومستحقة (لم تتجاوز مدة السماح)
                ->orWhere(function ($sub) use ($today, $totalGraceDays) {
                    $sub->whereNotNull('delay_duration')
                        ->where('delay_duration', '>', 0)
                        ->whereRaw("DATE_ADD(due_date_start, INTERVAL delay_duration DAY) <= ?", [$today])
                        ->whereRaw("DATE_ADD(DATE_ADD(due_date_start, INTERVAL delay_duration DAY), INTERVAL ? DAY) >= ?", [$totalGraceDays, $today]);
                });
            });
    }

    /**
     * Scope for postponed payments (still within postponement period).
     */
    public function scopePostponedPayments($query)
    {
        $today = Carbon::now()->startOfDay()->toDateString();

        return $query->whereNull('collection_date')
            ->whereNotNull('delay_duration')
            ->where('delay_duration', '>', 0)
            ->whereRaw("DATE_ADD(due_date_start, INTERVAL delay_duration DAY) >= ?", [$today]);
    }

    /**
     * Scope for overdue payments (including expired postponements).
     */
    public function scopeOverduePayments($query)
    {
        $today = Carbon::now()->startOfDay()->toDateString();
        $totalGraceDays = (new self)->getTotalGraceThreshold();

        return $query->whereNull('collection_date')
            ->where(function ($q) use ($today, $totalGraceDays) {
                // دفعات بدون تأجيل ومتأخرة
                $q->where(function ($sub) use ($today, $totalGraceDays) {
                    $sub->where(function ($s) {
                        $s->whereNull('delay_duration')->orWhere('delay_duration', 0);
                    })->whereRaw("DATE_ADD(due_date_start, INTERVAL ? DAY) < ?", [$totalGraceDays, $today]);
                })
                // أو دفعات منتهية التأجيل ومتأخرة
                ->orWhere(function ($sub) use ($today, $totalGraceDays) {
                    $sub->whereNotNull('delay_duration')
                        ->where('delay_duration', '>', 0)
                        ->whereRaw("DATE_ADD(DATE_ADD(due_date_start, INTERVAL delay_duration DAY), INTERVAL ? DAY) < ?", [$totalGraceDays, $today]);
                });
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

    // ==========================================
    // Methods
    // ==========================================

    /**
     * تأجيل الدفعة
     */
    public function postpone(int $days, string $reason): void
    {
        $this->update([
            'delay_duration' => $days,
            'delay_reason' => $reason,
        ]);
    }

    /**
     * تأكيد استلام الدفعة
     */
    public function markAsCollected(): void
    {
        $currentDate = Carbon::now();
        $this->update([
            'collection_date' => $currentDate,
            'paid_date' => $currentDate,
        ]);
    }

    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return 'COLLECTION-'.$year.'-'.str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * الحصول على أيام التأخير المسموح بها قبل اعتبار الدفعة متأخرة
     */
    public function getTotalGraceThreshold(): int
    {
        return (int) Setting::get('allowed_delay_days', 5);
    }

    public function calculateLateFee(): float
    {
        if (! $this->isOverdue()) {
            return 0.00;
        }

        $daysOverdue = $this->getDaysOverdue();
        $dailyFeeRate = 0.05; // 0.05% per day

        return round($this->amount * ($dailyFeeRate / 100) * $daysOverdue, 2);
    }

    public function getDaysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        $totalGraceDays = $this->getTotalGraceThreshold();
        $baseDate = $this->due_date_start;
        $overdueDate = ($baseDate instanceof Carbon ? $baseDate->copy() : Carbon::parse($baseDate))->addDays($totalGraceDays);

        return (int) Carbon::now()->startOfDay()->diffInDays($overdueDate);
    }

    /**
     * Check if payment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->payment_status === PaymentStatus::OVERDUE;
    }

    public function canTransitionTo($statusId): bool
    {
        // تم تبسيط المنطق ليعتمد على الـ Enum مباشرة
        $newStatus = is_string($statusId) ? PaymentStatus::from($statusId) : $statusId;

        // هنا يمكن إضافة قواعد الانتقال إذا لزم الأمر
        return true;
    }

    public function processPayment($paymentMethodId, $paidDate = null, $paymentReference = null): bool
    {
        $this->update([
            'payment_method_id' => $paymentMethodId,
            'paid_date' => $paidDate ?: Carbon::now()->toDateString(),
            'payment_reference' => $paymentReference,
            'payment_status_id' => PaymentStatus::COLLECTED,
            'receipt_number' => $this->generateReceiptNumber(),
        ]);

        return true;
    }

    public function generateReceiptNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('paid_date', $year)
            ->whereNotNull('receipt_number')
            ->count() + 1;

        return 'REC-'.$year.'-'.str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}

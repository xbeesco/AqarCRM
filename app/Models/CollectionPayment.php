<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use App\Models\Setting;
use App\Enums\PaymentStatus;

class CollectionPayment extends Model
{
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
        // تم حذف payment_status - سنحسبها ديناميكياً
        'payment_method_id',
        'amount',
        'late_fee',
        'total_amount',
        'due_date_start',
        'due_date_end',
        'paid_date',
        'collection_date',  // تاريخ التحصيل الفعلي
        'collected_by',     // الموظف الذي حصّل الدفعة
        'delay_duration',   // عدد أيام التأجيل
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
        // تم حذف cast payment_status - لا نحفظها في قاعدة البيانات
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            // توليد رقم الدفعة تلقائياً
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
            
            // تم حذف تعيين payment_status - سنحسبها ديناميكياً
            
            // قيمة افتراضية للغرامة
            if (is_null($payment->late_fee)) {
                $payment->late_fee = 0;
            }
            
            // حساب المجموع الكلي
            $payment->total_amount = ($payment->amount ?? 0) + ($payment->late_fee ?? 0);
            
            // توليد الشهر والسنة للتقارير
            if (empty($payment->month_year)) {
                // استخدم due_date_start إن وجد، وإلا استخدم التاريخ الحالي
                $dateForMonth = $payment->due_date_start ?? DateHelper::getCurrentDate();
                $payment->month_year = \Carbon\Carbon::parse($dateForMonth)->format('Y-m');
            }
        });

        static::updating(function ($payment) {
            // إعادة حساب المجموع الكلي
            $payment->total_amount = ($payment->amount ?? 0) + ($payment->late_fee ?? 0);
            
            // تحديث الشهر والسنة
            if (empty($payment->month_year) && !empty($payment->due_date_start)) {
                $payment->month_year = \Carbon\Carbon::parse($payment->due_date_start)->format('Y-m');
            }
        });
    }

    // Relationships
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

    // تم حذف علاقة paymentStatus لأننا نستخدم Enum الآن

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // Accessors للحصول على معلومات الحالة من Enum (محسوبة ديناميكياً)
    public function getPaymentStatusAttribute(): PaymentStatus
    {
        return $this->determinePaymentStatus();
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

    // Scopes
    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // تم دمج scopeOverdue مع scopeOverduePayments - استخدم scopeOverduePayments

    public function scopeByMonth($query, $monthYear)
    {
        return $query->where('month_year', $monthYear);
    }
    
    // تم حذف scopePostponed المكرر - استخدم scopePostponedPayments بدلاً منه
    
    public function scopePostponedWithDetails($query)
    {
        return $query->postponedPayments()
                     ->with(['tenant:id,name,phone', 'unit:id,name', 'property:id,name'])
                     ->select(['id', 'payment_number', 'tenant_id', 'unit_id', 'property_id', 
                              'amount', 'total_amount', 'delay_reason', 'delay_duration',
                              'due_date_start', 'due_date_end', 'late_payment_notes',
                              'created_at']);
    }
    
    public function scopeCriticalPostponed($query)
    {
        return $query->postponedPayments()
                     ->where(function($q) {
                         $q->where('delay_duration', '>', 30)
                           ->orWhere('due_date_end', '<', DateHelper::getCurrentDate()->subDays(30));
                     });
    }
    
    public function scopeRecentPostponed($query, $days = 7)
    {
        return $query->postponedPayments()
                     ->where('created_at', '>=', DateHelper::getCurrentDate()->subDays($days));
    }
    
    // New Scopes for actual payment status (not relying on collection_status field)
    
    /**
     * Scope للدفعات المستحقة للتحصيل
     * دفعات وصل تاريخ استحقاقها ولم تُحصّل ولا يوجد تأجيل
     */
    public function scopeDueForCollection($query)
    {
        $today = DateHelper::getCurrentDate()->startOfDay();
        return $query->where('due_date_start', '<=', $today)
                    ->whereNull('collection_date')
                    ->where(function($q) {
                        $q->whereNull('delay_duration')
                          ->orWhere('delay_duration', 0);
                    });
    }
    
    /**
     * Scope للدفعات المؤجلة
     * دفعات وصل تاريخ استحقاقها ولكن يوجد عدد أيام تأجيل
     */
    public function scopePostponedPayments($query)
    {
        $today = DateHelper::getCurrentDate()->startOfDay();
        return $query->where('due_date_start', '<=', $today)
                    ->whereNull('collection_date')
                    ->whereNotNull('delay_duration')
                    ->where('delay_duration', '>', 0);
    }
    
    /**
     * Scope للدفعات المتأخرة
     * دفعات تجاوزت مدة السماح المحددة في إعدادات النظام وليست مؤجلة
     */
    public function scopeOverduePayments($query)
    {
        $paymentDueDays = Setting::get('payment_due_days', 7);
        $today = DateHelper::getCurrentDate()->startOfDay();
        $overdueDate = $today->copy()->subDays($paymentDueDays);
        
        return $query->where('due_date_start', '<', $overdueDate)
                    ->whereNull('collection_date')
                    ->where(function($q) {
                        $q->whereNull('delay_duration')
                          ->orWhere('delay_duration', 0);
                    });
    }
    
    /**
     * Scope للدفعات المحصلة
     */
    public function scopeCollectedPayments($query)
    {
        return $query->whereNotNull('collection_date');
    }
    
    /**
     * Scope للدفعات القادمة (لم يحن موعدها بعد)
     */
    public function scopeUpcomingPayments($query)
    {
        $today = DateHelper::getCurrentDate()->startOfDay();
        return $query->whereNull('collection_date')
                    ->where('due_date_start', '>', $today);
    }
    
    /**
     * Scope لفلترة حسب حالة معينة (باستخدام الحساب الديناميكي)
     */
    public function scopeByStatus($query, PaymentStatus $status)
    {
        return match($status) {
            PaymentStatus::COLLECTED => $this->scopeCollectedPayments($query),
            PaymentStatus::POSTPONED => $this->scopePostponedPayments($query),
            PaymentStatus::OVERDUE => $this->scopeOverduePayments($query),
            PaymentStatus::DUE => $this->scopeDueForCollection($query),
            PaymentStatus::UPCOMING => $this->scopeUpcomingPayments($query),
        };
    }
    
    /**
     * Scope لفلترة حسب حالات متعددة (باستخدام الحساب الديناميكي)
     */
    public function scopeByStatuses($query, array $statuses)
    {
        return $query->where(function($q) use ($statuses) {
            foreach ($statuses as $status) {
                $statusEnum = $status instanceof PaymentStatus ? $status : PaymentStatus::from($status);
                $q->orWhere(function($subQuery) use ($statusEnum) {
                    $this->scopeByStatus($subQuery, $statusEnum);
                });
            }
        });
    }

    // Attributes using Enum
    /**
     * تحديد حالة الدفعة بناءً على البيانات الفعلية
     */
    public function determinePaymentStatus(): PaymentStatus
    {
        // إذا تم التحصيل
        if ($this->collection_date) {
            return PaymentStatus::COLLECTED;
        }
        
        // إذا كانت مؤجلة
        if ($this->delay_duration && $this->delay_duration > 0) {
            return PaymentStatus::POSTPONED;
        }
        
        $today = DateHelper::getCurrentDate()->startOfDay();
        $paymentsDueDays = Setting::get('payment_due_days', 7);
        $overdueDate = $today->copy()->subDays($paymentsDueDays);
        
        // إذا كانت متأخرة (تجاوزت مدة السماح)
        if ($this->due_date_start < $overdueDate) {
            return PaymentStatus::OVERDUE;
        }
        
        // إذا كانت مستحقة (وصل تاريخها لكن لم تتجاوز مدة السماح)
        if ($this->due_date_start <= $today) {
            return PaymentStatus::DUE;
        }
        
        // إذا كانت قادمة (لم يصل تاريخها بعد)
        return PaymentStatus::UPCOMING;
    }
    
    /**
     * الحصول على حالة الدفعة باستخدام Enum
     */
    public function getPaymentStatusEnumAttribute(): PaymentStatus
    {
        return $this->determinePaymentStatus();
    }
    
    // تم حذف getPaymentStatusLabelAttribute و getPaymentStatusColorAttribute المكررين
    // موجودين بالفعل في الأعلى (السطر 139 و 144)
        
    /**
     * هل يمكن تأجيل الدفعة؟
     */
    public function getCanBePostponedAttribute(): bool
    {
        return $this->collection_date === null && 
               ($this->delay_duration === null || $this->delay_duration == 0);
    }
    
    /**
     * هل يمكن تأكيد استلام الدفعة؟
     */
    public function getCanBeCollectedAttribute(): bool
    {
        return $this->collection_date === null;
    }
    
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
        $currentDate = DateHelper::getCurrentDate();
        $this->update([
            'collection_date' => $currentDate,
            'paid_date' => $currentDate,
        ]);
    }
    
    // Methods
    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'COLLECTION-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public function calculateLateFee(): float
    {
        if (!$this->isOverdue()) {
            return 0.00;
        }

        $daysOverdue = $this->getDaysOverdue();
        $dailyFeeRate = 0.05; // 5% per day (configurable)
        
        return round($this->amount * ($dailyFeeRate / 100) * $daysOverdue, 2);
    }

    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return Carbon::parse($this->due_date_end)->diffInDays(DateHelper::getCurrentDate());
    }

    public function isOverdue(): bool
    {
        return Carbon::parse($this->due_date_end)->isPast() && 
               !$this->paymentStatus->is_paid_status;
    }

    public function canTransitionTo($statusId): bool
    {
        $newStatus = PaymentStatus::find($statusId);
        return $this->paymentStatus->canTransitionTo($newStatus);
    }

    public function processPayment($paymentMethodId, $paidDate = null, $paymentReference = null): bool
    {
        $this->update([
            'payment_method_id' => $paymentMethodId,
            'paid_date' => $paidDate ?: DateHelper::getCurrentDate()->toDateString(),
            'payment_reference' => $paymentReference,
            'payment_status_id' => PaymentStatus::COLLECTED,
            'receipt_number' => $this->generateReceiptNumber(),
        ]);

        // Create transaction record
        $this->createTransaction();

        return true;
    }

    public function generateReceiptNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('paid_date', $year)
                    ->whereNotNull('receipt_number')
                    ->count() + 1;
        return 'REC-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    private function createTransaction(): void
    {
        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'collection_payment',
            'transactionable_type' => CollectionPayment::class,
            'transactionable_id' => $this->id,
            'property_id' => $this->property_id,
            'debit_amount' => $this->total_amount,
            'credit_amount' => 0.00,
            'description' => "Payment collection for unit {$this->unit->name} - {$this->month_year}",
            'transaction_date' => $this->paid_date,
            'reference_number' => $this->payment_number,
            'meta_data' => [
                'tenant_name' => $this->tenant->name,
                'unit_name' => $this->unit->name,
                'property_name' => $this->property->name,
            ]
        ]);
    }
    // ==========================================
    // Scopes إضافية جديدة لدعم ميزة إعادة الجدولة
    // Additional Scopes for Rescheduling Feature
    // ==========================================
    
    /**
     * Scope للدفعات المحصلة (بناءً على collection_date)
     * Scope for paid/collected payments
     */
    public function scopePaid($query)
    {
        return $query->whereNotNull('collection_date');
    }
    
    /**
     * Scope للدفعات غير المحصلة (بناءً على collection_date)
     * Scope for unpaid payments
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNull('collection_date');
    }
}
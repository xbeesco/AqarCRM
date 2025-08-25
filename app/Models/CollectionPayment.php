<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Carbon\Carbon;

class CollectionPayment extends Model
{
    protected $fillable = [
        'payment_number',
        'unit_contract_id',
        'unit_id',
        'property_id',
        'tenant_id',
        'collection_status', // حالة التحصيل
        'payment_status_id',
        'payment_method_id',
        'amount',
        'late_fee',
        'total_amount',
        'due_date_start',
        'due_date_end',
        'paid_date',
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
        'delay_duration' => 'integer',
    ];
    
    // حالات التحصيل
    const STATUS_COLLECTED = 'collected';      // تم التحصيل
    const STATUS_DUE = 'due';                  // تستحق التحصيل
    const STATUS_POSTPONED = 'postponed';      // المؤجلة
    const STATUS_OVERDUE = 'overdue';          // تجاوزت المدة
    
    public static function getStatusOptions()
    {
        return [
            self::STATUS_COLLECTED => 'تم التحصيل',
            self::STATUS_DUE => 'تستحق التحصيل',
            self::STATUS_POSTPONED => 'المؤجلة',
            self::STATUS_OVERDUE => 'تجاوزت المدة',
        ];
    }
    
    public function getStatusColorAttribute()
    {
        return match($this->collection_status) {
            self::STATUS_COLLECTED => 'success',
            self::STATUS_DUE => 'warning',
            self::STATUS_POSTPONED => 'info',
            self::STATUS_OVERDUE => 'danger',
            default => 'gray',
        };
    }
    
    public function getStatusLabelAttribute()
    {
        return self::getStatusOptions()[$this->collection_status] ?? $this->collection_status;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            // توليد رقم الدفعة تلقائياً
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
            
            // ضبط payment_status_id بناءً على collection_status
            if (empty($payment->payment_status_id)) {
                $payment->payment_status_id = match($payment->collection_status) {
                    self::STATUS_COLLECTED => 1,   // تم التحصيل
                    self::STATUS_DUE => 2,          // تستحق التحصيل
                    self::STATUS_POSTPONED => 3,    // المؤجلة
                    self::STATUS_OVERDUE => 4,      // تجاوزت المدة
                    default => 2,
                };
            }
            
            
            // قيمة افتراضية للغرامة
            if (is_null($payment->late_fee)) {
                $payment->late_fee = 0;
            }
            
            // حساب المجموع الكلي
            $payment->total_amount = ($payment->amount ?? 0) + ($payment->late_fee ?? 0);
            
            // توليد الشهر والسنة للتقارير
            if (empty($payment->month_year) && !empty($payment->due_date_start)) {
                $payment->month_year = \Carbon\Carbon::parse($payment->due_date_start)->format('Y-m');
            }
        });

        static::updating(function ($payment) {
            // ضبط payment_status_id بناءً على collection_status
            $payment->payment_status_id = match($payment->collection_status) {
                self::STATUS_COLLECTED => 1,   // تم التحصيل
                self::STATUS_DUE => 2,          // تستحق التحصيل
                self::STATUS_POSTPONED => 3,    // المؤجلة
                self::STATUS_OVERDUE => 4,      // تجاوزت المدة
                default => 2,
            };
            
            
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

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
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

    public function scopeOverdue($query)
    {
        return $query->where('due_date_end', '<', now())
                    ->whereHas('paymentStatus', function($q) {
                        $q->where('is_paid_status', false);
                    });
    }

    public function scopeByMonth($query, $monthYear)
    {
        return $query->where('month_year', $monthYear);
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

        return Carbon::parse($this->due_date_end)->diffInDays(now());
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
            'paid_date' => $paidDate ?: now()->toDateString(),
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
            'description' => "Payment collection for unit {$this->unit->unit_number} - {$this->month_year}",
            'transaction_date' => $this->paid_date,
            'reference_number' => $this->payment_number,
            'meta_data' => [
                'tenant_name' => $this->tenant->name,
                'unit_number' => $this->unit->unit_number,
                'property_name' => $this->property->name,
            ]
        ]);
    }
}
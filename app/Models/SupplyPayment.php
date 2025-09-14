<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SupplyPayment extends Model
{
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
        'supply_status',
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
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
            
            // Calculate net amount
            $payment->net_amount = $payment->calculateNetAmount();
        });

        static::updating(function ($payment) {
            // Recalculate net amount
            $payment->net_amount = $payment->calculateNetAmount();
        });
    }

    // Relationships
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

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
    
    // علاقات وهمية للاستخدام في صفحة العرض
    public function collectionPayments()
    {
        // علاقة وهمية - البيانات الفعلية تأتي من getCollectionPaymentsDetails()
        return $this->hasMany(\App\Models\CollectionPayment::class, 'property_id', 'property_id');
    }
    
    public function expenses()
    {
        // علاقة وهمية - البيانات الفعلية تأتي من getExpensesDetails()
        return $this->hasMany(\App\Models\Expense::class, 'subject_id', 'property_id');
    }
    
    /**
     * حساب قيمة دفعة التوريد بناءً على المدى الزمني
     * يحسب إجمالي المبالغ المحصلة خلال الفترة مع خصم العمولة والمصروفات
     * Calculate supply payment amounts using PaymentAssignmentService
     */
    public function calculateAmountsFromPeriod(): array
    {
        // استخراج المدى الزمني من invoice_details (يتضمن فترة السماح بالفعل)
        $invoiceDetails = $this->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $this->month_year . '-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        // 1. حساب إجمالي المبالغ المحصلة خلال الفترة باستخدام خدمة تخصيص الدفعات
        $paymentService = app(\App\Services\PaymentAssignmentService::class);
        $collectionData = $paymentService->calculateCollectedAmountsForPeriod(
            $this->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );

        $collectedAmount = $collectionData['total_amount'];
        $collectionsCount = $collectionData['payments_count'];

        // 2. حساب العمولة
        $commissionAmount = round($collectedAmount * ($this->commission_rate / 100), 2);

        // 3. حساب المصروفات خلال الفترة (للعقار والوحدات التابعة له)
        // جلب معرفات الوحدات التابعة للعقار
        $unitIds = \App\Models\Unit::where('property_id', $this->propertyContract->property_id)
            ->pluck('id')
            ->toArray();

        // حساب مصروفات العقار
        $propertyExpenses = \App\Models\Expense::where('subject_type', \App\Models\Property::class)
            ->where('subject_id', $this->propertyContract->property_id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->sum('cost');

        // حساب مصروفات الوحدات
        $unitExpenses = 0;
        if (!empty($unitIds)) {
            $unitExpenses = \App\Models\Expense::where('subject_type', \App\Models\Unit::class)
                ->whereIn('subject_id', $unitIds)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->sum('cost');
        }

        $expenses = $propertyExpenses + $unitExpenses;

        // 4. حساب المبلغ الصافي
        $netAmount = $collectedAmount - $commissionAmount - $expenses;

        return [
            'gross_amount' => $collectedAmount,
            'commission_amount' => $commissionAmount,
            'maintenance_deduction' => $expenses,
            'net_amount' => $netAmount,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'collections_count' => $collectionsCount
        ];
    }
    
    /**
     * الحصول على دفعات التحصيل التفصيلية للفترة
     * Get detailed collection payments using PaymentAssignmentService
     */
    public function getCollectionPaymentsDetails(): \Illuminate\Database\Eloquent\Collection
    {
        // استخراج المدى الزمني
        $invoiceDetails = $this->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $this->month_year . '-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        // استخدام خدمة تخصيص الدفعات
        $paymentService = app(\App\Services\PaymentAssignmentService::class);

        return $paymentService->getPaymentsForPeriod(
            $this->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );
    }
    
    /**
     * الحصول على المصروفات التفصيلية للفترة
     */
    public function getExpensesDetails(): \Illuminate\Database\Eloquent\Collection
    {
        $invoiceDetails = $this->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $this->month_year . '-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        // جلب معرفات الوحدات التابعة للعقار
        $unitIds = \App\Models\Unit::where('property_id', $this->propertyContract->property_id)
            ->pluck('id')
            ->toArray();

        // جلب النفقات للعقار والوحدات معاً
        return \App\Models\Expense::where(function($query) use ($unitIds) {
                // نفقات العقار
                $query->where(function($q) {
                    $q->where('subject_type', \App\Models\Property::class)
                      ->where('subject_id', $this->propertyContract->property_id);
                })
                // أو نفقات الوحدات
                ->orWhere(function($q) use ($unitIds) {
                    if (!empty($unitIds)) {
                        $q->where('subject_type', \App\Models\Unit::class)
                          ->whereIn('subject_id', $unitIds);
                    }
                });
            })
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->orderBy('date', 'desc')
            ->get();
    }

    // Accessors للحالة الديناميكية
    /**
     * Override حقل supply_status ليكون ديناميكي
     * الحالة تعتمد على التواريخ فقط
     */
    public function getSupplyStatusAttribute(): string
    {
        // إذا تم التوريد
        if ($this->paid_date) {
            return 'collected';
        }

        // إذا حل تاريخ الاستحقاق ولم يتم التوريد
        if ($this->due_date <= \Carbon\Carbon::now()) {
            return 'worth_collecting';
        }

        // إذا لم يحل تاريخ الاستحقاق بعد
        return 'pending';
    }

    /**
     * الحصول على التسمية العربية للحالة
     */
    public function getSupplyStatusLabelAttribute(): string
    {
        return match($this->supply_status) {
            'pending' => 'قيد الانتظار',
            'worth_collecting' => 'تستحق التوريد',
            'collected' => 'تم التوريد',
            default => $this->supply_status,
        };
    }

    /**
     * الحصول على اللون المناسب للحالة
     */
    public function getSupplyStatusColorAttribute(): string
    {
        return match($this->supply_status) {
            'pending' => 'warning',
            'worth_collecting' => 'info',
            'collected' => 'success',
            default => 'gray',
        };
    }

    // Scopes
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopePending($query)
    {
        return $query->where('supply_status', 'pending');
    }

    public function scopeWorthCollecting($query)
    {
        return $query->where('supply_status', 'worth_collecting');
    }

    public function scopeCollected($query)
    {
        return $query->where('supply_status', 'collected');
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

    // Methods
    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'SUPPLY-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    public function calculateNetAmount(): float
    {
        return $this->gross_amount - $this->commission_amount - $this->maintenance_deduction - $this->other_deductions;
    }

    public function calculateCommission(): float
    {
        return round(($this->gross_amount * $this->commission_rate) / 100, 2);
    }

    public function approve($approverId): bool
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'supply_status' => 'worth_collecting',
        ]);

        return true;
    }

    public function reject($approverId, $reason = null): bool
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'notes' => $reason ? "Rejected: {$reason}" : 'Rejected',
        ]);

        return true;
    }

    public function processPayment($bankTransferReference = null): bool
    {
        $this->update([
            'supply_status' => 'collected',
            'paid_date' => now()->toDateString(),
            'bank_transfer_reference' => $bankTransferReference,
        ]);

        // Create transaction record
        $this->createTransaction();

        return true;
    }

    public function requiresApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function getDeductionBreakdown(): array
    {
        return [
            'commission' => [
                'amount' => $this->commission_amount,
                'rate' => $this->commission_rate . '%',
                'description' => 'Management commission',
            ],
            'maintenance' => [
                'amount' => $this->maintenance_deduction,
                'description' => 'Maintenance and repairs',
            ],
            'other' => [
                'amount' => $this->other_deductions,
                'description' => 'Other deductions',
                'details' => $this->deduction_details,
            ]
        ];
    }

    private function createTransaction(): void
    {
        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'supply_payment',
            'transactionable_type' => SupplyPayment::class,
            'transactionable_id' => $this->id,
            'property_id' => $this->propertyContract->property_id ?? null,
            'debit_amount' => 0.00,
            'credit_amount' => $this->net_amount,
            'description' => "Owner payment for property contract - {$this->month_year}",
            'transaction_date' => $this->paid_date,
            'reference_number' => $this->payment_number,
            'meta_data' => [
                'owner_name' => $this->owner->name,
                'gross_amount' => $this->gross_amount,
                'deductions' => $this->getDeductionBreakdown(),
                'bank_reference' => $this->bank_transfer_reference,
            ]
        ]);
    }

    /**
     * تحديد ما إذا كانت دفعة التحصيل تنتمي لهذه دفعة التوريد
     * Determines if a collection payment belongs to this supply payment period
     *
     * Business Logic:
     * 1. Primary: Use due_date_start to determine original period
     * 2. Exception: If payment is VERY late (crosses into new supply payment period), assign to new period
     * 3. Early payments: Always stay with original period
     *
     * @param \App\Models\CollectionPayment $payment
     * @return bool
     */
}
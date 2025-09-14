<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Services\SupplyPaymentService;

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
            $service = app(SupplyPaymentService::class);

            if (empty($payment->payment_number)) {
                $payment->payment_number = $service->generatePaymentNumber();
            }

            // Calculate net amount
            $payment->net_amount = $service->calculateNetAmount($payment);
        });

        static::updating(function ($payment) {
            $service = app(SupplyPaymentService::class);
            // Recalculate net amount
            $payment->net_amount = $service->calculateNetAmount($payment);
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
        // علاقة وهمية - البيانات الفعلية تأتي من Service
        return $this->hasMany(\App\Models\CollectionPayment::class, 'property_id', 'property_id');
    }

    public function expenses()
    {
        // علاقة وهمية - البيانات الفعلية تأتي من Service
        return $this->hasMany(\App\Models\Expense::class, 'subject_id', 'property_id');
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

    // Helper Methods - استخدام Service للعمليات المعقدة
    public function requiresApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * الحصول على دفعات التحصيل مصنفة حسب الأنواع الستة
     * Get categorized collection payments for this supply payment
     */
    public function getCategorizedCollectionPayments(): array
    {
        $paymentAssignmentService = app(\App\Services\PaymentAssignmentService::class);
        $invoiceDetails = $this->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $this->month_year . '-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        return $paymentAssignmentService->getCategorizedPaymentsForPeriod(
            $this->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );
    }

    /**
     * الحصول على ملخص إحصائي لدفعات التحصيل
     * Get collection payments summary
     */
    public function getCollectionPaymentsSummary(): array
    {
        $categorizedData = $this->getCategorizedCollectionPayments();

        $summary = [
            'total_payments' => 0,
            'total_amount' => 0,
            'counted_payments' => 0,
            'counted_amount' => $categorizedData['counted_total'],
            'uncounted_payments' => 0,
            'uncounted_amount' => $categorizedData['uncounted_total'],
            'by_type' => []
        ];

        foreach ($categorizedData['categories'] as $type => $category) {
            $count = $category['payments']->count();
            $total = $category['total'];

            $summary['total_payments'] += $count;
            $summary['total_amount'] += $total;

            if ($category['counted']) {
                $summary['counted_payments'] += $count;
            } else {
                $summary['uncounted_payments'] += $count;
            }

            $summary['by_type'][$type] = [
                'name' => $category['name'],
                'count' => $count,
                'total' => $total,
                'counted' => $category['counted']
            ];
        }

        return $summary;
    }

    /**
     * التحقق من صحة الحسابات
     * Validate calculations match expected values
     */
    public function validateCalculations(): array
    {
        $service = app(\App\Services\SupplyPaymentService::class);
        $calculated = $service->calculateAmountsFromPeriod($this);

        $errors = [];

        // فقط تحقق إذا كانت الدفعة محصلة
        if ($this->supply_status === 'collected') {
            if (abs($this->gross_amount - $calculated['gross_amount']) > 0.01) {
                $errors[] = [
                    'field' => 'gross_amount',
                    'stored' => $this->gross_amount,
                    'calculated' => $calculated['gross_amount'],
                    'difference' => $this->gross_amount - $calculated['gross_amount']
                ];
            }

            if (abs($this->net_amount - $calculated['net_amount']) > 0.01) {
                $errors[] = [
                    'field' => 'net_amount',
                    'stored' => $this->net_amount,
                    'calculated' => $calculated['net_amount'],
                    'difference' => $this->net_amount - $calculated['net_amount']
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'calculated' => $calculated
        ];
    }
}
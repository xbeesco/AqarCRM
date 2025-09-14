<?php

namespace App\Services;

use App\Models\SupplyPayment;
use App\Models\PropertyContract;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\Expense;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SupplyPaymentService
{
    protected PaymentAssignmentService $paymentAssignmentService;

    public function __construct(PaymentAssignmentService $paymentAssignmentService)
    {
        $this->paymentAssignmentService = $paymentAssignmentService;
    }

    /**
     * توليد رقم فريد لدفعة التوريد
     */
    public function generatePaymentNumber(): string
    {
        $year = date('Y');
        $count = SupplyPayment::whereYear('created_at', $year)->count() + 1;
        return 'SUPPLY-' . $year . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * حساب المبلغ الصافي
     */
    public function calculateNetAmount(SupplyPayment $payment): float
    {
        return $payment->gross_amount - $payment->commission_amount - $payment->maintenance_deduction - $payment->other_deductions;
    }

    /**
     * حساب العمولة
     */
    public function calculateCommission(SupplyPayment $payment): float
    {
        return round(($payment->gross_amount * $payment->commission_rate) / 100, 2);
    }

    /**
     * حساب قيمة دفعة التوريد بناءً على المدى الزمني
     * يحسب إجمالي المبالغ المحصلة خلال الفترة مع خصم العمولة والمصروفات
     */
    public function calculateAmountsFromPeriod(SupplyPayment $payment): array
    {
        // استخراج المدى الزمني من invoice_details (يتضمن فترة السماح بالفعل)
        $invoiceDetails = $payment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $payment->month_year . '-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        // 1. حساب إجمالي المبالغ المحصلة خلال الفترة باستخدام خدمة تخصيص الدفعات
        $collectionData = $this->paymentAssignmentService->calculateCollectedAmountsForPeriod(
            $payment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );

        $collectedAmount = $collectionData['total_amount'];
        $collectionsCount = $collectionData['payments_count'];

        // 2. حساب العمولة
        $commissionAmount = round($collectedAmount * ($payment->commission_rate / 100), 2);

        // 3. حساب المصروفات خلال الفترة (للعقار والوحدات التابعة له)
        $expenses = $this->calculateExpensesForPeriod(
            $payment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );

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
     * حساب المصروفات للفترة المحددة
     */
    protected function calculateExpensesForPeriod(int $propertyId, string $periodStart, string $periodEnd): float
    {
        // جلب معرفات الوحدات التابعة للعقار
        $unitIds = Unit::where('property_id', $propertyId)
            ->pluck('id')
            ->toArray();

        // حساب مصروفات العقار
        $propertyExpenses = Expense::where('subject_type', Property::class)
            ->where('subject_id', $propertyId)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->sum('cost');

        // حساب مصروفات الوحدات
        $unitExpenses = 0;
        if (!empty($unitIds)) {
            $unitExpenses = Expense::where('subject_type', Unit::class)
                ->whereIn('subject_id', $unitIds)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->sum('cost');
        }

        return $propertyExpenses + $unitExpenses;
    }

    /**
     * الحصول على دفعات التحصيل التفصيلية للفترة
     */
    public function getCollectionPaymentsDetails(SupplyPayment $payment): Collection
    {
        // استخراج المدى الزمني
        $invoiceDetails = $payment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $payment->month_year . '-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        return $this->paymentAssignmentService->getPaymentsForPeriod(
            $payment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );
    }

    /**
     * الحصول على المصروفات التفصيلية للفترة
     */
    public function getExpensesDetails(SupplyPayment $payment): Collection
    {
        $invoiceDetails = $payment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $payment->month_year . '-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        // جلب معرفات الوحدات التابعة للعقار
        $unitIds = Unit::where('property_id', $payment->propertyContract->property_id)
            ->pluck('id')
            ->toArray();

        // جلب النفقات للعقار والوحدات معاً
        return Expense::where(function($query) use ($payment, $unitIds) {
                // نفقات العقار
                $query->where(function($q) use ($payment) {
                    $q->where('subject_type', Property::class)
                      ->where('subject_id', $payment->propertyContract->property_id);
                })
                // أو نفقات الوحدات
                ->orWhere(function($q) use ($unitIds) {
                    if (!empty($unitIds)) {
                        $q->where('subject_type', Unit::class)
                          ->whereIn('subject_id', $unitIds);
                    }
                });
            })
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * اعتماد دفعة التوريد
     */
    public function approve(SupplyPayment $payment, int $approverId): bool
    {
        $payment->update([
            'approval_status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'supply_status' => 'worth_collecting',
        ]);

        return true;
    }

    /**
     * رفض دفعة التوريد
     */
    public function reject(SupplyPayment $payment, int $approverId, ?string $reason = null): bool
    {
        $payment->update([
            'approval_status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'notes' => $reason ? "Rejected: {$reason}" : 'Rejected',
        ]);

        return true;
    }

    /**
     * معالجة الدفع
     */
    public function processPayment(SupplyPayment $payment, ?string $bankTransferReference = null): bool
    {
        $payment->update([
            'supply_status' => 'collected',
            'paid_date' => now()->toDateString(),
            'bank_transfer_reference' => $bankTransferReference,
        ]);

        // Create transaction record
        $this->createTransaction($payment);

        return true;
    }

    /**
     * الحصول على تفصيل الخصومات
     */
    public function getDeductionBreakdown(SupplyPayment $payment): array
    {
        return [
            'commission' => [
                'amount' => $payment->commission_amount,
                'rate' => $payment->commission_rate . '%',
                'description' => 'عمولة الإدارة',
            ],
            'maintenance' => [
                'amount' => $payment->maintenance_deduction,
                'description' => 'الصيانة والإصلاحات',
            ],
            'other' => [
                'amount' => $payment->other_deductions,
                'description' => 'خصومات أخرى',
                'details' => $payment->deduction_details,
            ]
        ];
    }

    /**
     * إنشاء معاملة مالية
     */
    protected function createTransaction(SupplyPayment $payment): void
    {
        Transaction::create([
            'transaction_number' => Transaction::generateTransactionNumber(),
            'type' => 'supply_payment',
            'transactionable_type' => SupplyPayment::class,
            'transactionable_id' => $payment->id,
            'property_id' => $payment->propertyContract->property_id ?? null,
            'debit_amount' => 0.00,
            'credit_amount' => $payment->net_amount,
            'description' => "توريد دفعة للمالك - {$payment->month_year}",
            'transaction_date' => $payment->paid_date,
            'reference_number' => $payment->payment_number,
            'meta_data' => [
                'owner_name' => $payment->owner->name,
                'gross_amount' => $payment->gross_amount,
                'deductions' => $this->getDeductionBreakdown($payment),
                'bank_reference' => $payment->bank_transfer_reference,
            ]
        ]);
    }

    /**
     * التحقق من وجود دفعات سابقة غير مؤكدة لنفس العقد
     */
    public function hasPendingPreviousPayments(SupplyPayment $payment): bool
    {
        return SupplyPayment::where('property_contract_id', $payment->property_contract_id)
            ->where('due_date', '<', $payment->due_date)
            ->whereNull('paid_date')
            ->exists();
    }

    /**
     * الحصول على الدفعات السابقة غير المؤكدة لنفس العقد
     */
    public function getPendingPreviousPayments(SupplyPayment $payment): Collection
    {
        return SupplyPayment::where('property_contract_id', $payment->property_contract_id)
            ->where('due_date', '<', $payment->due_date)
            ->whereNull('paid_date')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * تأكيد دفعة التوريد مع جميع الفحوصات المطلوبة
     */
    public function confirmSupplyPayment(SupplyPayment $payment, int $userId): array
    {
        // التحقق من الدفعات السابقة
        if ($this->hasPendingPreviousPayments($payment)) {
            $pendingPayments = $this->getPendingPreviousPayments($payment);

            return [
                'success' => false,
                'message' => 'لا يمكن تأكيد التوريد - يوجد دفعات سابقة لم يتم توريدها بعد',
                'pending_payments' => $pendingPayments,
                'details' => $pendingPayments->map(function ($p) {
                    $amounts = $this->calculateAmountsFromPeriod($p);
                    return [
                        'payment_number' => $p->payment_number,
                        'month_year' => $p->month_year,
                        'due_date' => $p->due_date->format('Y-m-d'),
                        'net_amount' => $amounts['net_amount'],
                        'status' => $p->supply_status
                    ];
                })
            ];
        }

        // حساب المبالغ
        $amounts = $this->calculateAmountsFromPeriod($payment);

        // تحديد نوع العملية
        $isSettlement = $amounts['net_amount'] <= 0;

        // تحديث الدفعة
        $payment->update([
            'gross_amount' => $amounts['gross_amount'],
            'commission_amount' => $amounts['commission_amount'],
            'maintenance_deduction' => $amounts['maintenance_deduction'],
            'net_amount' => $amounts['net_amount'],
            'supply_status' => 'collected',
            'paid_date' => now(),
            'collected_by' => $userId,
        ]);

        // إنشاء معاملة مالية إذا لم تكن تسوية صفرية
        if ($amounts['net_amount'] != 0) {
            $this->createTransaction($payment);
        }

        // إعداد رسالة النجاح
        if ($isSettlement) {
            if ($amounts['net_amount'] < 0) {
                $message = sprintf(
                    'تم تسجيل دين بقيمة %s ريال على المالك %s',
                    number_format(abs($amounts['net_amount']), 2),
                    $payment->owner?->name
                );
            } else {
                $message = sprintf(
                    'تم تأكيد التسوية - لا توجد مستحقات للمالك %s',
                    $payment->owner?->name
                );
            }
        } else {
            $message = sprintf(
                'تم توريد مبلغ %s ريال للمالك %s',
                number_format($amounts['net_amount'], 2),
                $payment->owner?->name
            );
        }

        return [
            'success' => true,
            'message' => $message,
            'is_settlement' => $isSettlement,
            'payment' => $payment,
            'amounts' => $amounts
        ];
    }

    /**
     * التحقق من إمكانية تأكيد التوريد
     */
    public function canConfirmPayment(SupplyPayment $payment): array
    {
        $errors = [];

        // التحقق من الحالة
        if ($payment->supply_status === 'collected') {
            $errors[] = 'تم توريد هذه الدفعة مسبقاً';
        }

        // التحقق من تاريخ الاستحقاق
        if (!$payment->due_date) {
            $errors[] = 'لا يوجد تاريخ استحقاق محدد';
        } elseif (now()->lt($payment->due_date)) {
            $errors[] = 'لم يحل موعد الاستحقاق بعد';
        }

        // التحقق من الدفعات السابقة
        if ($this->hasPendingPreviousPayments($payment)) {
            $pendingCount = $this->getPendingPreviousPayments($payment)->count();
            $errors[] = "يوجد {$pendingCount} دفعة/دفعات سابقة لم يتم توريدها";
        }

        return [
            'can_confirm' => empty($errors),
            'errors' => $errors
        ];
    }
}
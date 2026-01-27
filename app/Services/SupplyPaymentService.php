<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Property;
use App\Models\SupplyPayment;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;

class SupplyPaymentService
{
    protected PaymentAssignmentService $paymentAssignmentService;

    public function __construct(PaymentAssignmentService $paymentAssignmentService)
    {
        $this->paymentAssignmentService = $paymentAssignmentService;
    }

    /**
     * Generate unique payment number.
     *
     * @deprecated Use SupplyPayment::generateNewPaymentNumber() or let the model auto-generate the number
     */
    public function generatePaymentNumber(): string
    {
        return SupplyPayment::generateNewPaymentNumber();
    }

    /**
     * Calculate net amount.
     */
    public function calculateNetAmount(SupplyPayment $payment): float
    {
        return $payment->gross_amount - $payment->commission_amount - $payment->maintenance_deduction - $payment->other_deductions;
    }

    /**
     * Calculate commission amount.
     */
    public function calculateCommission(SupplyPayment $payment): float
    {
        return round(($payment->gross_amount * $payment->commission_rate) / 100, 2);
    }

    /**
     * Calculate supply payment amounts based on period.
     */
    public function calculateAmountsFromPeriod(SupplyPayment $payment): array
    {
        $invoiceDetails = $payment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $payment->month_year.'-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        $collectionData = $this->paymentAssignmentService->calculateCollectedAmountsForPeriod(
            $payment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );

        $collectedAmount = $collectionData['total_amount'];
        $collectionsCount = $collectionData['payments_count'];

        $commissionAmount = round($collectedAmount * ($payment->commission_rate / 100), 2);

        $expenses = $this->calculateExpensesForPeriod(
            $payment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );

        $netAmount = $collectedAmount - $commissionAmount - $expenses;

        return [
            'gross_amount' => $collectedAmount,
            'commission_amount' => $commissionAmount,
            'maintenance_deduction' => $expenses,
            'net_amount' => $netAmount,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'collections_count' => $collectionsCount,
        ];
    }

    /**
     * Calculate expenses for a given period.
     */
    protected function calculateExpensesForPeriod(int $propertyId, string $periodStart, string $periodEnd): float
    {
        $unitIds = Unit::where('property_id', $propertyId)
            ->pluck('id')
            ->toArray();

        $propertyExpenses = Expense::where('subject_type', Property::class)
            ->where('subject_id', $propertyId)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->sum('cost');

        $unitExpenses = 0;
        if (! empty($unitIds)) {
            $unitExpenses = Expense::where('subject_type', Unit::class)
                ->whereIn('subject_id', $unitIds)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->sum('cost');
        }

        return $propertyExpenses + $unitExpenses;
    }

    /**
     * Get detailed collection payments for period.
     */
    public function getCollectionPaymentsDetails(SupplyPayment $payment): Collection
    {
        $invoiceDetails = $payment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $payment->month_year.'-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        return $this->paymentAssignmentService->getPaymentsForPeriod(
            $payment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );
    }

    /**
     * Get detailed expenses for period.
     */
    public function getExpensesDetails(SupplyPayment $payment): Collection
    {
        $invoiceDetails = $payment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $payment->month_year.'-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        $unitIds = Unit::where('property_id', $payment->propertyContract->property_id)
            ->pluck('id')
            ->toArray();

        return Expense::where(function ($query) use ($payment, $unitIds) {
            $query->where(function ($q) use ($payment) {
                $q->where('subject_type', Property::class)
                    ->where('subject_id', $payment->propertyContract->property_id);
            })
                ->orWhere(function ($q) use ($unitIds) {
                    if (! empty($unitIds)) {
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
     * Approve a supply payment.
     */
    public function approve(SupplyPayment $payment, int $approverId): bool
    {
        $payment->update([
            'approval_status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Reject a supply payment.
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
     * Process payment with bank reference.
     */
    public function processPayment(SupplyPayment $payment, ?string $bankTransferReference = null): bool
    {
        $payment->update([
            'paid_date' => now()->toDateString(),
            'bank_transfer_reference' => $bankTransferReference,
        ]);

        return true;
    }

    /**
     * Get deduction breakdown.
     */
    public function getDeductionBreakdown(SupplyPayment $payment): array
    {
        return [
            'commission' => [
                'amount' => $payment->commission_amount,
                'rate' => $payment->commission_rate.'%',
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
            ],
        ];
    }

    /**
     * Check if there are pending previous payments for the same contract.
     */
    public function hasPendingPreviousPayments(SupplyPayment $payment): bool
    {
        return SupplyPayment::where('property_contract_id', $payment->property_contract_id)
            ->where('due_date', '<', $payment->due_date)
            ->whereNull('paid_date')
            ->exists();
    }

    /**
     * Get pending previous payments for the same contract.
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
     * Confirm supply payment with all required checks.
     */
    public function confirmSupplyPayment(SupplyPayment $payment, int $userId): array
    {
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
                        'status' => $p->supply_status,
                    ];
                }),
            ];
        }

        $amounts = $this->calculateAmountsFromPeriod($payment);
        $isSettlement = $amounts['net_amount'] <= 0;

        $payment->update([
            'gross_amount' => $amounts['gross_amount'],
            'commission_amount' => $amounts['commission_amount'],
            'maintenance_deduction' => $amounts['maintenance_deduction'],
            'net_amount' => $amounts['net_amount'],
            'paid_date' => now(),
            'collected_by' => $userId,
        ]);

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
            'amounts' => $amounts,
        ];
    }

    /**
     * Check if payment can be confirmed.
     */
    public function canConfirmPayment(SupplyPayment $payment): array
    {
        $errors = [];

        if ($payment->paid_date !== null) {
            $errors[] = 'تم توريد هذه الدفعة مسبقاً';
        }

        if (! $payment->due_date) {
            $errors[] = 'لا يوجد تاريخ استحقاق محدد';
        } elseif (now()->lt($payment->due_date)) {
            $errors[] = 'لم يحل موعد الاستحقاق بعد';
        }

        if ($this->hasPendingPreviousPayments($payment)) {
            $pendingCount = $this->getPendingPreviousPayments($payment)->count();
            $errors[] = "يوجد {$pendingCount} دفعة/دفعات سابقة لم يتم توريدها";
        }

        return [
            'can_confirm' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get categorized collection payments.
     */
    public function getCategorizedCollectionPayments(SupplyPayment $payment): array
    {
        $invoiceDetails = $payment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $payment->month_year.'-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        return $this->paymentAssignmentService->getCategorizedPaymentsForPeriod(
            $payment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );
    }

    /**
     * Get collection payments summary.
     */
    public function getCollectionPaymentsSummary(SupplyPayment $payment): array
    {
        $categorizedData = $this->getCategorizedCollectionPayments($payment);

        $summary = [
            'total_payments' => 0,
            'total_amount' => 0,
            'counted_payments' => 0,
            'counted_amount' => $categorizedData['counted_total'],
            'uncounted_payments' => 0,
            'uncounted_amount' => $categorizedData['uncounted_total'],
            'by_type' => [],
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
                'counted' => $category['counted'],
            ];
        }

        return $summary;
    }

    /**
     * Validate payment calculations.
     */
    public function validateCalculations(SupplyPayment $payment): array
    {
        $calculated = $this->calculateAmountsFromPeriod($payment);

        $errors = [];

        if ($payment->paid_date !== null) {
            if (abs($payment->gross_amount - $calculated['gross_amount']) > 0.01) {
                $errors[] = [
                    'field' => 'gross_amount',
                    'stored' => $payment->gross_amount,
                    'calculated' => $calculated['gross_amount'],
                    'difference' => $payment->gross_amount - $calculated['gross_amount'],
                ];
            }

            if (abs($payment->net_amount - $calculated['net_amount']) > 0.01) {
                $errors[] = [
                    'field' => 'net_amount',
                    'stored' => $payment->net_amount,
                    'calculated' => $calculated['net_amount'],
                    'difference' => $payment->net_amount - $calculated['net_amount'],
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'calculated' => $calculated,
        ];
    }
}

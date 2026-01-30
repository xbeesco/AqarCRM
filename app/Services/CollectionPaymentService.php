<?php

namespace App\Services;

use Exception;
use App\Enums\PaymentStatus;
use App\Models\CollectionPayment;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class CollectionPaymentService
{
    /**
     * Generate unique payment number.
     *
     * @deprecated Use CollectionPayment::generateNewPaymentNumber() or let the model auto-generate the number
     */
    public function generatePaymentNumber(): string
    {
        return CollectionPayment::generateNewPaymentNumber();
    }

    /**
     * Generate receipt number.
     */
    public function generateReceiptNumber(): string
    {
        $year = date('Y');
        $count = CollectionPayment::whereYear('paid_date', $year)
            ->whereNotNull('receipt_number')
            ->count() + 1;

        return 'REC-'.$year.'-'.str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate late fee.
     */
    public function calculateLateFee(CollectionPayment $payment): float
    {
        if (! $this->isOverdue($payment)) {
            return 0.00;
        }

        $daysOverdue = $this->getDaysOverdue($payment);
        $dailyFeeRate = Setting::get('late_fee_daily_rate', 0.05);

        return round($payment->amount * ($dailyFeeRate / 100) * $daysOverdue, 2);
    }

    /**
     * Get days overdue.
     */
    public function getDaysOverdue(CollectionPayment $payment): int
    {
        if (! $this->isOverdue($payment)) {
            return 0;
        }

        return Carbon::parse($payment->due_date_end)->diffInDays(Carbon::now());
    }

    /**
     * Check if payment is overdue.
     */
    public function isOverdue(CollectionPayment $payment): bool
    {
        return Carbon::parse($payment->due_date_end)->isPast() &&
               $payment->collection_date === null;
    }

    /**
     * Calculate total amount.
     */
    public function calculateTotalAmount(CollectionPayment $payment): float
    {
        return ($payment->amount ?? 0) + ($payment->late_fee ?? 0);
    }

    /**
     * Process payment collection.
     */
    public function processPayment(
        CollectionPayment $payment,
        int $paymentMethodId,
        ?string $paidDate = null,
        ?string $paymentReference = null
    ): bool {
        $currentDate = Carbon::now();

        $payment->update([
            'payment_method_id' => $paymentMethodId,
            'paid_date' => $paidDate ?: $currentDate->toDateString(),
            'collection_date' => $currentDate,
            'payment_reference' => $paymentReference,
            'receipt_number' => $this->generateReceiptNumber(),
        ]);

        return true;
    }

    /**
     * Bulk collect multiple payments.
     */
    public function bulkCollectPayments(
        array $paymentIds,
        int $paymentMethodId,
        ?string $paidDate = null
    ): array {
        $results = [];
        $payments = CollectionPayment::whereIn('id', $paymentIds)->get();

        foreach ($payments as $payment) {
            try {
                $success = $this->processPayment(
                    $payment,
                    $paymentMethodId,
                    $paidDate
                );

                $results[] = [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'success' => $success,
                    'receipt_number' => $success ? $payment->receipt_number : null,
                ];
            } catch (Exception $e) {
                $results[] = [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Postpone payment.
     */
    public function postponePayment(CollectionPayment $payment, int $days, string $reason): bool
    {
        if (! $this->canBePostponed($payment)) {
            return false;
        }

        $payment->update([
            'delay_duration' => $days,
            'delay_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if payment can be postponed.
     */
    public function canBePostponed(CollectionPayment $payment): bool
    {
        return $payment->collection_date === null &&
               ($payment->delay_duration === null || $payment->delay_duration == 0);
    }

    /**
     * Check if payment can be collected.
     */
    public function canBeCollected(CollectionPayment $payment): bool
    {
        return $payment->collection_date === null;
    }

    /**
     * Mark payment as collected.
     */
    public function markAsCollected(CollectionPayment $payment, ?int $collectedBy = null): bool
    {
        $currentDate = Carbon::now();

        $payment->update([
            'collection_date' => $currentDate,
            'paid_date' => $currentDate,
            'collected_by' => $collectedBy,
        ]);

        return true;
    }

    /**
     * Update late fees for overdue payments.
     */
    public function updateOverduePayments(): int
    {
        $overduePayments = CollectionPayment::overduePayments()->get();
        $updatedCount = 0;

        foreach ($overduePayments as $payment) {
            $lateFee = $this->calculateLateFee($payment);

            $payment->update([
                'late_fee' => $lateFee,
                'total_amount' => $payment->amount + $lateFee,
            ]);

            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * Reconcile payments with bank statement.
     */
    public function reconcilePayments(array $bankStatementData): array
    {
        $reconciled = [];
        $unmatched = [];

        foreach ($bankStatementData as $bankRecord) {
            $payment = CollectionPayment::where('payment_reference', $bankRecord['reference'])
                ->orWhere('receipt_number', $bankRecord['reference'])
                ->first();

            if ($payment) {
                $reconciled[] = [
                    'bank_record' => $bankRecord,
                    'payment' => $payment,
                    'amount_match' => abs($bankRecord['amount'] - $payment->total_amount) < 0.01,
                ];
            } else {
                $unmatched[] = $bankRecord;
            }
        }

        return [
            'reconciled' => $reconciled,
            'unmatched' => $unmatched,
            'total_bank_records' => count($bankStatementData),
            'matched_count' => count($reconciled),
        ];
    }

    /**
     * Generate payment report.
     */
    public function generatePaymentReport(array $filters): array
    {
        $query = CollectionPayment::query();

        if (isset($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['unit_id'])) {
            $query->where('unit_id', $filters['unit_id']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('due_date_end', [$filters['date_from'], $filters['date_to']]);
        }

        if (isset($filters['month_year'])) {
            $query->where('month_year', $filters['month_year']);
        }

        // Filter by status using scopes
        if (isset($filters['status'])) {
            $status = $filters['status'];
            if ($status instanceof PaymentStatus) {
                $query->byStatus($status);
            } elseif (is_string($status)) {
                $statusEnum = PaymentStatus::tryFrom($status);
                if ($statusEnum) {
                    $query->byStatus($statusEnum);
                }
            }
        }

        $payments = $query->with(['property', 'unit', 'tenant'])->get();

        // Calculate amounts by status
        $collectedAmount = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::COLLECTED)->sum('total_amount');
        $overdueAmount = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::OVERDUE)->sum('total_amount');
        $dueAmount = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::DUE)->sum('total_amount');
        $upcomingAmount = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::UPCOMING)->sum('total_amount');
        $postponedAmount = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::POSTPONED)->sum('total_amount');

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'collected_amount' => $collectedAmount,
            'overdue_amount' => $overdueAmount,
            'due_amount' => $dueAmount,
            'upcoming_amount' => $upcomingAmount,
            'postponed_amount' => $postponedAmount,
            'payments' => $payments->toArray(),
        ];
    }

    /**
     * Get payment summary for tenant.
     */
    public function getTenantPaymentSummary(int $tenantId): array
    {
        $payments = CollectionPayment::where('tenant_id', $tenantId)->get();

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'collected_amount' => $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::COLLECTED)->sum('total_amount'),
            'pending_amount' => $payments->filter(fn ($p) => $p->collection_date === null)->sum('total_amount'),
            'overdue_count' => $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::OVERDUE)->count(),
        ];
    }

    /**
     * Get payment summary for property.
     */
    public function getPropertyPaymentSummary(int $propertyId): array
    {
        $payments = CollectionPayment::where('property_id', $propertyId)->get();

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'collected_amount' => $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::COLLECTED)->sum('total_amount'),
            'pending_amount' => $payments->filter(fn ($p) => $p->collection_date === null)->sum('total_amount'),
            'overdue_count' => $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::OVERDUE)->count(),
        ];
    }

    /**
     * Get payments due for collection.
     */
    public function getDueForCollection(?int $propertyId = null): Collection
    {
        $query = CollectionPayment::dueForCollection()
            ->with(['tenant', 'unit', 'property']);

        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        return $query->orderBy('due_date_start')->get();
    }

    /**
     * Get overdue payments.
     */
    public function getOverduePayments(?int $propertyId = null): Collection
    {
        $query = CollectionPayment::overduePayments()
            ->with(['tenant', 'unit', 'property']);

        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        return $query->orderBy('due_date_start')->get();
    }

    /**
     * Get postponed payments.
     */
    public function getPostponedPayments(?int $propertyId = null): Collection
    {
        $query = CollectionPayment::postponedPayments()
            ->with(['tenant', 'unit', 'property']);

        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        return $query->orderBy('due_date_start')->get();
    }

    /**
     * Get critical postponed payments (over 30 days).
     */
    public function getCriticalPostponedPayments(): Collection
    {
        return CollectionPayment::criticalPostponed()
            ->with(['tenant', 'unit', 'property'])
            ->orderBy('due_date_start')
            ->get();
    }
}

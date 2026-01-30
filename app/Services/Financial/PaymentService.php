<?php

namespace App\Services\Financial;

use Exception;
use App\Models\PropertyContract;
use App\Enums\PaymentStatus;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Services\CollectionPaymentService;
use App\Services\SupplyPaymentService;

class PaymentService
{
    public function __construct(
        protected CollectionPaymentService $collectionPaymentService,
        protected SupplyPaymentService $supplyPaymentService
    ) {}

    public function processCollectionPayment(
        CollectionPayment $payment,
        int $paymentMethodId,
        ?string $paidDate = null,
        ?string $paymentReference = null
    ): bool {
        return $this->collectionPaymentService->processPayment($payment, $paymentMethodId, $paidDate, $paymentReference);
    }

    public function bulkCollectPayments(
        array $paymentIds,
        int $paymentMethodId,
        ?string $paidDate = null
    ): array {
        $results = [];
        $payments = CollectionPayment::whereIn('id', $paymentIds)->get();

        foreach ($payments as $payment) {
            try {
                $success = $this->processCollectionPayment(
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
     * Update late fees for overdue payments.
     * Note: Status is computed dynamically and not stored in the database.
     */
    public function updateOverduePayments(): int
    {
        // Get overdue payments using the Scope
        $overduePayments = CollectionPayment::overduePayments()->get();

        $updatedCount = 0;

        foreach ($overduePayments as $payment) {
            $lateFee = $this->collectionPaymentService->calculateLateFee($payment);

            // Update late fee only - status is computed dynamically
            $payment->update([
                'late_fee' => $lateFee,
                'total_amount' => $payment->amount + $lateFee,
            ]);

            $updatedCount++;
        }

        return $updatedCount;
    }

    public function reconcilePayments(array $bankStatementData): array
    {
        $reconciled = [];
        $unmatched = [];

        foreach ($bankStatementData as $bankRecord) {
            $payment = CollectionPayment::where('payment_reference', $bankRecord['reference'])
                ->orWhere('receipt_number', $bankRecord['reference'])
                ->first();

            if ($payment) {
                // Match found
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

    public function calculateOwnerPayment(int $propertyContractId, string $monthYear): array
    {
        // Get property contract to find the property_id
        $propertyContract = PropertyContract::find($propertyContractId);

        if (! $propertyContract) {
            return [
                'gross_amount' => 0,
                'commission_rate' => 10.0,
                'commission_amount' => 0,
                'maintenance_deduction' => 0,
                'other_deductions' => 0.00,
                'net_amount' => 0,
                'collection_payments' => [],
            ];
        }

        // Get all collected payments for this property and month
        // Note: Collected payments are those with a collection_date
        $collectionPayments = CollectionPayment::where('property_id', $propertyContract->property_id)
            ->where('month_year', $monthYear)
            ->collectedPayments()  // Use Scope instead of paymentStatus relationship
            ->get();

        $grossAmount = $collectionPayments->sum('total_amount');

        // Get commission rate from property contract
        $commissionRate = $propertyContract->commission_rate ?? 10.0;
        $commissionAmount = ($grossAmount * $commissionRate) / 100;

        // Get maintenance deductions for this period
        $maintenanceDeduction = $this->getMaintenanceDeductions($propertyContractId, $monthYear);

        $netAmount = $grossAmount - $commissionAmount - $maintenanceDeduction;

        return [
            'gross_amount' => $grossAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'maintenance_deduction' => $maintenanceDeduction,
            'other_deductions' => 0.00,
            'net_amount' => $netAmount,
            'collection_payments' => $collectionPayments->pluck('payment_number')->toArray(),
        ];
    }

    public function processSupplyPaymentApproval(SupplyPayment $payment, int $approverId): bool
    {
        return $this->supplyPaymentService->approve($payment, $approverId);
    }

    public function executeSupplyPayment(SupplyPayment $payment, ?string $bankTransferReference = null): bool
    {
        return $this->supplyPaymentService->processPayment($payment, $bankTransferReference);
    }

    private function getMaintenanceDeductions(int $propertyContractId, string $monthYear): float
    {
        // This would query property repairs for the given period
        // For now, return 0
        return 0.00;
    }

    /**
     * Generate payment report.
     *
     * @param  array  $filters  Available filters: property_id, date_from, date_to, status (enum string)
     */
    public function generatePaymentReport(array $filters): array
    {
        $query = CollectionPayment::query();

        if (isset($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('due_date_end', [$filters['date_from'], $filters['date_to']]);
        }

        // Filter by status using Scopes
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

        // Calculate amounts by dynamic status
        $collectedAmount = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::COLLECTED)->sum('total_amount');
        $overdueAmount = $payments->filter(fn ($p) => $p->payment_status === PaymentStatus::OVERDUE)->sum('total_amount');

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'collected_amount' => $collectedAmount,
            'overdue_amount' => $overdueAmount,
            'payments' => $payments->toArray(),
        ];
    }
}

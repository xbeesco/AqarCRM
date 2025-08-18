<?php

namespace App\Services\Financial;

use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\PaymentStatus;
use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

class PaymentService
{
    public function processCollectionPayment(
        CollectionPayment $payment, 
        int $paymentMethodId, 
        ?string $paidDate = null, 
        ?string $paymentReference = null
    ): bool {
        return $payment->processPayment($paymentMethodId, $paidDate, $paymentReference);
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
            } catch (\Exception $e) {
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

    public function updateOverduePayments(): int
    {
        $overdueStatus = PaymentStatus::where('slug', 'overdue')->first();
        
        if (!$overdueStatus) {
            return 0;
        }

        $overduePayments = CollectionPayment::where('due_date_end', '<', now())
            ->whereHas('paymentStatus', function($q) {
                $q->where('is_paid_status', false);
            })->get();

        $updatedCount = 0;

        foreach ($overduePayments as $payment) {
            $lateFee = $payment->calculateLateFee();
            
            $payment->update([
                'payment_status_id' => $overdueStatus->id,
                'late_fee' => $lateFee,
                'total_amount' => $payment->amount + $lateFee,
                'delay_duration' => $payment->getDaysOverdue(),
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
        // Get all collection payments for this property contract and month
        $collectionPayments = CollectionPayment::whereHas('unitContract.propertyContract', function($q) use ($propertyContractId) {
            $q->where('id', $propertyContractId);
        })
        ->where('month_year', $monthYear)
        ->whereHas('paymentStatus', function($q) {
            $q->where('is_paid_status', true);
        })->get();

        $grossAmount = $collectionPayments->sum('total_amount');
        
        // Get commission rate from property contract
        $commissionRate = 10.0; // Default, should come from contract
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
        return $payment->approve($approverId);
    }

    public function executeSupplyPayment(SupplyPayment $payment, ?string $bankTransferReference = null): bool
    {
        return $payment->processPayment($bankTransferReference);
    }

    private function getMaintenanceDeductions(int $propertyContractId, string $monthYear): float
    {
        // This would query property repairs for the given period
        // For now, return 0
        return 0.00;
    }

    public function generatePaymentReport(array $filters): array
    {
        $query = CollectionPayment::query();

        if (isset($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('due_date_end', [$filters['date_from'], $filters['date_to']]);
        }

        if (isset($filters['status_id'])) {
            $query->where('payment_status_id', $filters['status_id']);
        }

        $payments = $query->with(['property', 'unit', 'tenant', 'paymentStatus'])->get();

        return [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('total_amount'),
            'collected_amount' => $payments->whereHas('paymentStatus', function($q) {
                $q->where('is_paid_status', true);
            })->sum('total_amount'),
            'overdue_amount' => $payments->where('paymentStatus.slug', 'overdue')->sum('total_amount'),
            'payments' => $payments->toArray(),
        ];
    }
}
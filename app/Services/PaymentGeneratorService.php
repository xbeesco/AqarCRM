<?php

namespace App\Services;

use InvalidArgumentException;
use Exception;
use App\Models\CollectionPayment;
use App\Models\PropertyContract;
use App\Models\Setting;
use App\Models\SupplyPayment;
use App\Models\UnitContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentGeneratorService
{
    /**
     * Generate tenant collection payments.
     */
    public function generateTenantPayments(UnitContract $contract): array
    {
        if (! $contract->monthly_rent || $contract->monthly_rent <= 0) {
            throw new InvalidArgumentException('Monthly rent amount is invalid');
        }

        DB::beginTransaction();

        try {
            $payments = [];
            $startDate = Carbon::parse($contract->start_date);
            $endDate = Carbon::parse($contract->end_date);
            $frequency = $contract->payment_frequency ?? 'monthly';

            // Calculate payment count
            $paymentCount = $this->calculatePaymentCount($startDate, $endDate, $frequency);

            if ($paymentCount <= 0) {
                throw new InvalidArgumentException('Invalid payment count for the specified period');
            }

            // Base payment amount
            $baseAmount = $this->calculatePaymentAmount($contract->monthly_rent, $frequency);

            $currentDate = $startDate->copy();
            $paymentNumber = 1;

            while ($currentDate <= $endDate && $paymentNumber <= $paymentCount) {
                // Calculate period end date
                $periodEnd = $this->calculatePeriodEnd($currentDate, $frequency);

                // Ensure we don't exceed contract end date
                if ($periodEnd > $endDate) {
                    $periodEnd = $endDate;
                    // Calculate prorated amount for the last period
                    $daysInPeriod = $currentDate->diffInDays($periodEnd) + 1;
                    $fullPeriodDays = $this->getFullPeriodDays($frequency);
                    $baseAmount = ($contract->monthly_rent * ($daysInPeriod / 30));
                }

                // Create the payment
                // Note: payment_status is computed dynamically from collection_date and due_date_start
                // Note: payment_number is auto-generated via HasPaymentNumber trait
                $payment = CollectionPayment::create([
                    'unit_contract_id' => $contract->id,
                    'unit_id' => $contract->unit_id,
                    'property_id' => $contract->property_id,
                    'tenant_id' => $contract->tenant_id,
                    'amount' => $baseAmount,
                    'late_fee' => 0,
                    'total_amount' => $baseAmount,
                    'due_date_start' => $currentDate->format('Y-m-d'),
                    'due_date_end' => $periodEnd->format('Y-m-d'),
                    'month_year' => $currentDate->format('Y-m'),
                ]);

                $payments[] = $payment;

                // Move to next period
                $currentDate = $this->getNextPeriodStart($currentDate, $frequency);
                $paymentNumber++;
            }

            DB::commit();

            return $payments;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate payment count based on frequency.
     */
    private function calculatePaymentCount($startDate, $endDate, $frequency): int
    {
        $months = $startDate->diffInMonths($endDate) + 1;
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);

        if ($monthsPerPayment === 0) {
            return 0;
        }

        return intval($months / $monthsPerPayment);
    }

    /**
     * Calculate payment amount based on frequency.
     */
    private function calculatePaymentAmount($monthlyRent, $frequency): float
    {
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);

        return $monthlyRent * $monthsPerPayment;
    }

    /**
     * Calculate period end date.
     */
    private function calculatePeriodEnd($startDate, $frequency): Carbon
    {
        $date = $startDate->copy();
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);

        return $date->addMonths($monthsPerPayment)->subDay();
    }

    /**
     * Get next period start date.
     */
    private function getNextPeriodStart($currentDate, $frequency): Carbon
    {
        $date = $currentDate->copy();
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);

        return $date->addMonths($monthsPerPayment);
    }

    /**
     * Get number of days in a full period.
     */
    private function getFullPeriodDays($frequency): int
    {
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);

        return $monthsPerPayment * 30; // Approximately 30 days per month
    }

    /**
     * Generate expected supply payment schedule for owner contract (for advance planning).
     * This function is used when creating a contract to generate all expected payments upfront.
     */
    public function generateSupplyPaymentsForContract(PropertyContract $contract): int
    {
        // Check if payments have already been generated
        if ($contract->supplyPayments()->exists()) {
            $count = $contract->supplyPayments()->count();
            throw new Exception("لا يمكن إنشاء دفعات جديدة - يوجد بالفعل {$count} دفعة لهذا العقد");
        }

        // Comprehensive validation for payment generation eligibility
        if (! $contract->canGeneratePayments()) {
            // Identify the specific issue
            if (! is_numeric($contract->payments_count) || $contract->payments_count <= 0) {
                throw new Exception('عدد الدفعات غير صحيح - يرجى التحقق من بيانات العقد');
            }

            if (! $contract->isValidDurationForFrequency()) {
                throw new Exception('مدة العقد لا تتطابق مع تكرار الدفع المحدد');
            }

            throw new Exception('لا يمكن إنشاء دفعات لهذا العقد - يرجى التحقق من صحة البيانات');
        }

        // Additional validation of duration and frequency compatibility
        if (! $contract->isValidDurationForFrequency()) {
            throw new Exception('Contract duration does not match the specified payment frequency');
        }

        $paymentsToGenerate = $contract->payments_count;

        DB::beginTransaction();

        try {
            $payments = [];
            $currentDate = Carbon::parse($contract->start_date);
            $endDate = Carbon::parse($contract->end_date);

            for ($i = 1; $i <= $paymentsToGenerate; $i++) {
                // Calculate period end date
                $periodEnd = $this->calculatePeriodEnd($currentDate, $contract->payment_frequency);

                // Ensure we don't exceed contract end date
                if ($periodEnd > $endDate) {
                    $periodEnd = $endDate->copy();
                }

                $payments[] = $this->createSupplyPayment($contract, $i, $currentDate, $periodEnd);

                // Move to next period
                $currentDate = $this->getNextPeriodStart($currentDate, $contract->payment_frequency);

                // Stop generation if we exceed end date
                if ($currentDate > $endDate) {
                    break;
                }
            }

            DB::commit();

            return count($payments);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a single supply payment.
     * Note: payment_number is auto-generated via HasPaymentNumber trait.
     */
    private function createSupplyPayment(
        PropertyContract $contract,
        int $paymentNumber,
        Carbon $periodStart,
        Carbon $periodEnd
    ): SupplyPayment {
        return SupplyPayment::create([
            'property_contract_id' => $contract->id,
            'owner_id' => $contract->owner_id,
            'gross_amount' => 0,
            'commission_amount' => 0,
            'commission_rate' => $contract->commission_rate,
            'maintenance_deduction' => 0,
            'other_deductions' => 0,
            'net_amount' => 0,
            'supply_status' => 'pending',
            'due_date' => $periodEnd->copy()->addDays($this->getPaymentDueDays()),
            'approval_status' => 'pending',
            'month_year' => $periodStart->format('Y-m'),
            'notes' => sprintf(
                'Payment %d of %d - %s',
                $paymentNumber,
                $contract->payments_count,
                $this->getFrequencyLabel($contract->payment_frequency)
            ),
            'invoice_details' => [
                'contract_number' => $contract->contract_number,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'payment_frequency' => $contract->payment_frequency,
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Get frequency label.
     */
    private function getFrequencyLabel(string $frequency): string
    {
        return match ($frequency) {
            'monthly' => 'شهري',
            'quarterly' => 'ربع سنوي',
            'semi_annually' => 'نصف سنوي',
            'annually' => 'سنوي',
            default => 'شهري'
        };
    }

    /**
     * Get payment due days from settings.
     */
    private function getPaymentDueDays(): int
    {
        return (int) Setting::get('payment_due_days', 5);
    }

    /**
     * Reschedule contract payments with full flexibility.
     * Paid payments remain unchanged, unpaid portion can be freely modified.
     */
    public function rescheduleContractPayments(
        UnitContract $contract,
        float $newMonthlyRent,
        int $additionalMonths,
        string $newFrequency
    ): array {
        // Validate input data
        if ($additionalMonths <= 0) {
            throw new InvalidArgumentException('يجب أن تكون الأشهر الإضافية أكبر من صفر');
        }

        if ($newMonthlyRent <= 0) {
            throw new InvalidArgumentException('يجب أن يكون مبلغ الإيجار أكبر من صفر');
        }

        // Validate duration compatibility with frequency
        if (! PropertyContractService::isValidDuration($additionalMonths, $newFrequency)) {
            throw new InvalidArgumentException('المدة الإضافية غير متوافقة مع تكرار التحصيل المحدد');
        }

        DB::beginTransaction();

        try {
            // 1. Get last paid period end date
            $lastPaidDate = $this->getLastPaidPeriodEnd($contract);

            // 2. Start date for new payments
            $newStartDate = $lastPaidDate ? $lastPaidDate->copy()->addDay() : Carbon::parse($contract->start_date);

            // 3. Delete all unpaid payments
            $deletedCount = $this->deleteUnpaidPayments($contract);

            // 4. Generate new payments
            $newPayments = $this->generatePaymentsFromDate(
                $contract,
                $newStartDate,
                $additionalMonths,
                $newFrequency,
                $newMonthlyRent
            );

            // 5. Calculate new contract end date
            $newEndDate = $newStartDate->copy()->addMonths($additionalMonths)->subDay();

            // 6. Calculate total new months
            $paidMonths = $this->calculatePaidMonths($contract);
            $totalMonths = $paidMonths + $additionalMonths;

            // 7. Update contract
            $contract->update([
                'end_date' => $newEndDate,
                'duration_months' => $totalMonths,
                'monthly_rent' => $newMonthlyRent,
                'notes' => $contract->notes . "\n[" . now()->format('Y-m-d H:i') . "] تمت إعادة جدولة الدفعات - حذف {$deletedCount} دفعة وإضافة " . count($newPayments) . ' دفعة جديدة',
            ]);

            DB::commit();

            return [
                'deleted_count' => $deletedCount,
                'new_payments' => $newPayments,
                'paid_months' => $paidMonths,
                'total_months' => $totalMonths,
                'new_end_date' => $newEndDate,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get last paid period end date.
     */
    public function getLastPaidPeriodEnd(UnitContract $contract): ?Carbon
    {
        $lastPaidPayment = $contract->collectionPayments()
            ->paid()  // Use new scope based on collection_date
            ->orderBy('due_date_end', 'desc')
            ->first();

        return $lastPaidPayment ? Carbon::parse($lastPaidPayment->due_date_end) : null;
    }

    /**
     * Delete all unpaid payments.
     */
    private function deleteUnpaidPayments(UnitContract $contract): int
    {
        return $contract->collectionPayments()
            ->unpaid()  // Use new scope based on collection_date
            ->delete();
    }

    /**
     * Calculate number of paid months.
     */
    private function calculatePaidMonths(UnitContract $contract): int
    {
        $paidPayments = $contract->collectionPayments()
            ->paid()  // Use new scope based on collection_date
            ->get();

        $totalDays = 0;
        foreach ($paidPayments as $payment) {
            $start = Carbon::parse($payment->due_date_start);
            $end = Carbon::parse($payment->due_date_end);
            $totalDays += $start->diffInDays($end) + 1;
        }

        // Convert days to months (approximately 30 days per month)
        return intval($totalDays / 30);
    }

    /**
     * Generate payments from a specific date.
     */
    private function generatePaymentsFromDate(
        UnitContract $contract,
        Carbon $startDate,
        int $durationMonths,
        string $frequency,
        float $monthlyRent
    ): array {
        $payments = [];
        $currentDate = $startDate->copy();
        $endDate = $startDate->copy()->addMonths($durationMonths)->subDay();

        // Calculate payment count
        $paymentCount = PropertyContractService::calculatePaymentsCount($durationMonths, $frequency);

        for ($i = 1; $i <= $paymentCount; $i++) {
            // Calculate period end date
            $periodEnd = $this->calculatePeriodEnd($currentDate, $frequency);

            // Ensure we don't exceed end date
            if ($periodEnd > $endDate) {
                $periodEnd = $endDate->copy();

                // Calculate prorated amount for the last period if needed
                $daysInPeriod = $currentDate->diffInDays($periodEnd) + 1;
                $monthsInPeriod = $daysInPeriod / 30;
                $paymentAmount = $monthlyRent * $monthsInPeriod;
            } else {
                // Full amount for the period
                $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);
                $paymentAmount = $monthlyRent * $monthsPerPayment;
            }

            // Create payment
            // Note: payment_number is auto-generated via HasPaymentNumber trait
            $payment = CollectionPayment::create([
                'unit_contract_id' => $contract->id,
                'unit_id' => $contract->unit_id,
                'property_id' => $contract->property_id,
                'tenant_id' => $contract->tenant_id,
                // Removed collection_status - status is computed dynamically
                'amount' => $paymentAmount,
                'late_fee' => 0,
                'total_amount' => $paymentAmount,
                'due_date_start' => $currentDate->format('Y-m-d'),
                'due_date_end' => $periodEnd->format('Y-m-d'),
                'month_year' => $currentDate->format('Y-m'),
            ]);

            $payments[] = $payment;

            // Move to next period
            $currentDate = $this->getNextPeriodStart($currentDate, $frequency);

            // Stop generation if we exceed end date
            if ($currentDate > $endDate) {
                break;
            }
        }

        return $payments;
    }

    /**
     * Reschedule property contract payments with full flexibility.
     * Paid payments remain unchanged, unpaid portion can be freely modified.
     */
    public function reschedulePropertyContractPayments(
        PropertyContract $contract,
        float $newCommissionRate,
        int $additionalMonths,
        string $newFrequency
    ): array {
        // Validate input data
        if ($additionalMonths <= 0) {
            throw new InvalidArgumentException('يجب أن تكون الأشهر الإضافية أكبر من صفر');
        }

        if ($newCommissionRate < 0 || $newCommissionRate > 100) {
            throw new InvalidArgumentException('يجب أن تكون نسبة العمولة بين 0 و 100');
        }

        // Validate duration compatibility with frequency
        if (!PropertyContractService::isValidDuration($additionalMonths, $newFrequency)) {
            throw new InvalidArgumentException('المدة الإضافية غير متوافقة مع تكرار التحصيل المحدد');
        }

        DB::beginTransaction();

        try {
            // 1. Get last paid period end date
            $lastPaidDate = $this->getLastPaidSupplyDate($contract);

            // 2. Start date for new payments
            $newStartDate = $lastPaidDate ? $lastPaidDate->copy()->addDay() : Carbon::parse($contract->start_date);

            // 3. Delete all unpaid payments
            $deletedCount = $this->deleteUnpaidSupplyPayments($contract);

            // 4. Calculate new payments count
            $newPaymentsCount = PropertyContractService::calculatePaymentsCount($additionalMonths, $newFrequency);

            // 5. Generate new payments
            $newPayments = $this->generateSupplyPaymentsFromDate(
                $contract,
                $newStartDate,
                $additionalMonths,
                $newFrequency,
                $newCommissionRate,
                $newPaymentsCount
            );

            // 6. Calculate new contract end date
            $newEndDate = $newStartDate->copy()->addMonths($additionalMonths)->subDay();

            // 7. Calculate total new months
            $paidMonths = $this->calculatePaidSupplyMonths($contract);
            $totalMonths = $paidMonths + $additionalMonths;

            // 8. Update contract
            $contract->update([
                'end_date' => $newEndDate,
                'duration_months' => $totalMonths,
                'commission_rate' => $newCommissionRate,
                'payment_frequency' => $newFrequency,
                'payments_count' => PropertyContractService::calculatePaymentsCount($totalMonths, $newFrequency),
                'notes' => $contract->notes . "\n[" . now()->format('Y-m-d H:i') . "] تمت إعادة جدولة الدفعات - حذف {$deletedCount} دفعة وإضافة " . count($newPayments) . ' دفعة جديدة',
            ]);

            DB::commit();

            return [
                'deleted_count' => $deletedCount,
                'new_payments' => $newPayments,
                'paid_months' => $paidMonths,
                'total_months' => $totalMonths,
                'new_end_date' => $newEndDate,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get last paid supply period end date.
     */
    private function getLastPaidSupplyDate(PropertyContract $contract): ?Carbon
    {
        $lastPaidPayment = $contract->supplyPayments()
            ->collected()
            ->orderBy('due_date', 'desc')
            ->first();

        return $lastPaidPayment ? Carbon::parse($lastPaidPayment->due_date) : null;
    }

    /**
     * Delete all unpaid supply payments.
     */
    private function deleteUnpaidSupplyPayments(PropertyContract $contract): int
    {
        return $contract->supplyPayments()
            ->whereNull('paid_date')
            ->delete();
    }

    /**
     * Calculate number of paid months for supply payments.
     */
    private function calculatePaidSupplyMonths(PropertyContract $contract): int
    {
        $paidPayments = $contract->supplyPayments()
            ->collected()
            ->get();

        if ($paidPayments->isEmpty()) {
            return 0;
        }

        // For supply payments, calculate based on payment frequency
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($contract->payment_frequency ?? 'monthly');

        return $paidPayments->count() * $monthsPerPayment;
    }

    /**
     * Generate supply payments from a specific date.
     */
    private function generateSupplyPaymentsFromDate(
        PropertyContract $contract,
        Carbon $startDate,
        int $durationMonths,
        string $frequency,
        float $commissionRate,
        int $paymentsCount
    ): array {
        $payments = [];
        $currentDate = $startDate->copy();
        $endDate = $startDate->copy()->addMonths($durationMonths)->subDay();

        for ($i = 1; $i <= $paymentsCount; $i++) {
            // Calculate period end date
            $periodEnd = $this->calculatePeriodEnd($currentDate, $frequency);

            // Ensure we don't exceed end date
            if ($periodEnd > $endDate) {
                $periodEnd = $endDate->copy();
            }

            // Create payment
            $payment = SupplyPayment::create([
                'property_contract_id' => $contract->id,
                'owner_id' => $contract->owner_id,
                'gross_amount' => 0,
                'commission_amount' => 0,
                'commission_rate' => $commissionRate,
                'maintenance_deduction' => 0,
                'other_deductions' => 0,
                'net_amount' => 0,
                'supply_status' => 'pending',
                'due_date' => $periodEnd->copy()->addDays($this->getPaymentDueDays()),
                'approval_status' => 'pending',
                'month_year' => $currentDate->format('Y-m'),
                'notes' => sprintf(
                    'الدفعة %d من %d - %s (معاد جدولتها)',
                    $i,
                    $paymentsCount,
                    $this->getFrequencyLabel($frequency)
                ),
                'invoice_details' => [
                    'contract_number' => $contract->contract_number,
                    'period_start' => $currentDate->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'payment_frequency' => $frequency,
                    'generated_at' => now()->toDateTimeString(),
                    'rescheduled' => true,
                ],
            ]);

            $payments[] = $payment;

            // Move to next period
            $currentDate = $this->getNextPeriodStart($currentDate, $frequency);

            // Stop generation if we exceed end date
            if ($currentDate > $endDate) {
                break;
            }
        }

        return $payments;
    }
}

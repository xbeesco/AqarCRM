<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PropertyContractService
{
    /**
     * Create a new property contract.
     */
    public function createContract(array $data): PropertyContract
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = Auth::id();

            $contract = PropertyContract::create($data);

            // Log contract creation
            activity()
                ->performedOn($contract)
                ->withProperties($data)
                ->log('Property contract created');

            return $contract;
        });
    }

    /**
     * Renew an existing contract.
     */
    public function renewContract(int $contractId, int $newDurationMonths, array $additionalData = []): PropertyContract
    {
        return DB::transaction(function () use ($contractId, $newDurationMonths, $additionalData) {
            $oldContract = PropertyContract::findOrFail($contractId);

            if (! $oldContract->canRenew()) {
                throw new \Exception('Contract is not eligible for renewal');
            }

            // Create new contract based on existing terms
            $newContractData = array_merge([
                'owner_id' => $oldContract->owner_id,
                'property_id' => $oldContract->property_id,
                'commission_rate' => $oldContract->commission_rate,
                'duration_months' => $newDurationMonths,
                'start_date' => $oldContract->end_date->addDay(),
                'payment_day' => $oldContract->payment_day,
                'auto_renew' => $oldContract->auto_renew,
                'notice_period_days' => $oldContract->notice_period_days,
                'terms_and_conditions' => $oldContract->terms_and_conditions,
                'contract_status' => 'active',
                'created_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ], $additionalData);

            $newContract = PropertyContract::create($newContractData);

            // Mark old contract as renewed
            $oldContract->update([
                'contract_status' => 'renewed',
                'notes' => $oldContract->notes . "\n\nRenewed with contract: " . $newContract->contract_number,
            ]);

            // Log renewal
            activity()
                ->performedOn($newContract)
                ->withProperties(['old_contract_id' => $oldContract->id])
                ->log('Property contract renewed');

            return $newContract;
        });
    }

    /**
     * Terminate a contract.
     */
    public function terminateContract(int $contractId, string $reason): PropertyContract
    {
        return DB::transaction(function () use ($contractId, $reason) {
            $contract = PropertyContract::findOrFail($contractId);

            if ($contract->contract_status !== 'active') {
                throw new \Exception('Only active contracts can be terminated');
            }

            $contract->update([
                'contract_status' => 'terminated',
                'terminated_reason' => $reason,
                'terminated_at' => now(),
            ]);

            // Update property status
            $availableStatusId = \App\Models\PropertyStatus::where('slug', 'available')->first()?->id ?? 1;
            $contract->property->update(['status_id' => $availableStatusId]);

            // Log termination
            activity()
                ->performedOn($contract)
                ->withProperties(['reason' => $reason])
                ->log('Property contract terminated');

            return $contract->fresh();
        });
    }

    /**
     * Process automatic renewals.
     */
    public function processAutoRenewals(): int
    {
        $contractsToRenew = PropertyContract::where('auto_renew', true)
            ->where('contract_status', 'active')
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>', now())
            ->get();

        $renewed = 0;

        foreach ($contractsToRenew as $contract) {
            try {
                $this->renewContract($contract->id, $contract->duration_months);
                $renewed++;
            } catch (\Exception $e) {
                // Log error but continue with other contracts
                \Log::error("Failed to auto-renew contract {$contract->contract_number}: " . $e->getMessage());
            }
        }

        return $renewed;
    }

    /**
     * Generate payment schedules for a contract.
     */
    public function generatePaymentSchedules(int $contractId): array
    {
        $contract = PropertyContract::with('property.units')->findOrFail($contractId);
        $schedules = [];

        // For each unit in the property, calculate commission payments
        foreach ($contract->property->units as $unit) {
            // This would integrate with the payment system
            // For now, we'll return the structure
            $schedules[] = [
                'unit_id' => $unit->id,
                'monthly_commission' => $contract->calculateCommission($unit->rent_amount ?? 0),
                'payment_day' => $contract->payment_day,
            ];
        }

        return $schedules;
    }

    /**
     * Get contracts expiring soon.
     */
    public function getExpiringContracts(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return PropertyContract::expiring($days)
            ->with(['owner', 'property'])
            ->get();
    }

    /**
     * Get owner's contract portfolio.
     */
    public function getOwnerPortfolio(int $ownerId): array
    {
        $contracts = PropertyContract::forOwner($ownerId)
            ->with(['property'])
            ->get();

        return [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('contract_status', 'active')->count(),
            'total_commission_rate' => $contracts->avg('commission_rate'),
            'contracts' => $contracts,
        ];
    }

    /**
     * Calculate payments count based on duration and frequency.
     */
    public static function calculatePaymentsCount(int $durationMonths, string $paymentFrequency): int|string
    {
        $monthsPerPayment = self::getMonthsPerPayment($paymentFrequency);

        if ($monthsPerPayment === 0) {
            return 0;
        }

        // Check division yields integer
        if ($durationMonths % $monthsPerPayment !== 0) {
            return 'قسمة لا تصح';
        }

        return $durationMonths / $monthsPerPayment;
    }

    /**
     * Validate duration is compatible with payment frequency.
     */
    public static function isValidDuration(int $durationMonths, string $paymentFrequency): bool
    {
        $monthsPerPayment = self::getMonthsPerPayment($paymentFrequency);

        if ($monthsPerPayment === 0) {
            return false;
        }

        return $durationMonths % $monthsPerPayment === 0;
    }

    /**
     * Get months per payment based on frequency.
     */
    public static function getMonthsPerPayment(string $paymentFrequency): int
    {
        return match ($paymentFrequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'annually' => 12,
            default => 1,
        };
    }

    /**
     * Calculate paid months count for property contract.
     */
    public function getPaidMonthsCount(PropertyContract $contract): int
    {
        $paidPayments = $contract->supplyPayments()
            ->collected()
            ->get();

        if ($paidPayments->isEmpty()) {
            return 0;
        }

        // For supply payments, we calculate based on payment frequency
        $monthsPerPayment = self::getMonthsPerPayment($contract->payment_frequency ?? 'monthly');

        return $paidPayments->count() * $monthsPerPayment;
    }

    /**
     * Calculate remaining adjustable months.
     */
    public function getRemainingMonths(PropertyContract $contract): int
    {
        $totalMonths = $contract->duration_months ?? 0;
        $paidMonths = $this->getPaidMonthsCount($contract);

        return max(0, $totalMonths - $paidMonths);
    }

    /**
     * Get paid payments count.
     */
    public function getPaidPaymentsCount(PropertyContract $contract): int
    {
        return $contract->supplyPayments()
            ->collected()
            ->count();
    }

    /**
     * Get unpaid payments count.
     */
    public function getUnpaidPaymentsCount(PropertyContract $contract): int
    {
        return $contract->supplyPayments()
            ->whereNull('paid_date')
            ->count();
    }
}

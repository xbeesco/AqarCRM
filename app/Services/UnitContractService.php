<?php

namespace App\Services;

use Exception;
use App\Models\UnitStatus;
use Illuminate\Support\Collection;
use App\Models\Unit;
use App\Models\UnitContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnitContractService
{
    /**
     * Create a new unit contract with enhanced validation.
     */
    public function createContract(array $data): UnitContract
    {
        return DB::transaction(function () use ($data) {
            // Lock the unit to prevent concurrent access
            $unit = Unit::where('id', $data['unit_id'])->lockForUpdate()->firstOrFail();

            // Calculate end date if not provided
            $endDate = $data['end_date'] ?? Carbon::parse($data['start_date'])
                ->addMonths($data['duration_months'])
                ->subDay();

            // Validate unit availability with enhanced checking
            $this->validateUnitAvailability(
                $data['unit_id'],
                $data['start_date'],
                $endDate
            );

            $data['created_by'] = Auth::id();
            $data['end_date'] = $endDate;

            $contract = UnitContract::create($data);

            // Log contract creation with security info
            activity()
                ->performedOn($contract)
                ->withProperties(array_merge($data, [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]))
                ->log('Unit contract created with overlap validation');

            return $contract;
        }, 5); // 5 attempts for deadlock retry
    }

    /**
     * Terminate a contract and release unit.
     */
    public function terminateContract(int $contractId, string $reason): UnitContract
    {
        return DB::transaction(function () use ($contractId, $reason) {
            $contract = UnitContract::findOrFail($contractId);

            if ($contract->contract_status !== 'active') {
                throw new Exception('Only active contracts can be terminated');
            }

            // Calculate early termination penalty if applicable
            $penalty = 0;
            if ($contract->canTerminateEarly()) {
                $penalty = $contract->calculateEarlyTerminationPenalty();
            }

            $contract->update([
                'contract_status' => 'terminated',
                'terminated_reason' => $reason,
                'terminated_at' => now(),
                'notes' => $contract->notes."\n\nEarly termination penalty: SAR ".number_format($penalty, 2),
            ]);

            // Mark unit as available (find available status ID)
            $availableStatusId = UnitStatus::where('slug', 'available')->first()?->id ?? 1;
            $contract->unit->update(['status_id' => $availableStatusId]);

            // Clear unit's current tenant
            $contract->unit->update(['current_tenant_id' => null]);

            // Log termination
            activity()
                ->performedOn($contract)
                ->withProperties(['reason' => $reason, 'penalty' => $penalty])
                ->log('Unit contract terminated');

            return $contract->fresh();
        });
    }

    /**
     * Renew an existing contract.
     */
    public function renewContract(int $contractId, int $newDurationMonths, array $additionalData = []): UnitContract
    {
        return DB::transaction(function () use ($contractId, $newDurationMonths, $additionalData) {
            $oldContract = UnitContract::findOrFail($contractId);

            if (! $oldContract->canRenew()) {
                throw new Exception('Contract is not eligible for renewal');
            }

            // Create new contract based on existing terms
            $newContractData = array_merge([
                'tenant_id' => $oldContract->tenant_id,
                'unit_id' => $oldContract->unit_id,
                'property_id' => $oldContract->property_id,
                'monthly_rent' => $oldContract->monthly_rent,
                'security_deposit' => $oldContract->security_deposit,
                'duration_months' => $newDurationMonths,
                'start_date' => $oldContract->end_date->addDay(),
                'payment_frequency' => $oldContract->payment_frequency,
                'payment_method' => $oldContract->payment_method,
                'grace_period_days' => $oldContract->grace_period_days,
                'late_fee_rate' => $oldContract->late_fee_rate,
                'utilities_included' => $oldContract->utilities_included,
                'furnished' => $oldContract->furnished,
                'evacuation_notice_days' => $oldContract->evacuation_notice_days,
                'terms_and_conditions' => $oldContract->terms_and_conditions,
                'contract_status' => 'active',
                'created_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ], $additionalData);

            $newContract = UnitContract::create($newContractData);

            // Mark old contract as renewed
            $oldContract->update([
                'contract_status' => 'renewed',
                'notes' => $oldContract->notes."\n\nRenewed with contract: ".$newContract->contract_number,
            ]);

            // Log renewal
            activity()
                ->performedOn($newContract)
                ->withProperties(['old_contract_id' => $oldContract->id])
                ->log('Unit contract renewed');

            return $newContract;
        });
    }

    /**
     * Generate payment schedules for a contract.
     */
    public function generateCollectionPayments(int $contractId): array
    {
        $contract = UnitContract::findOrFail($contractId);

        if ($contract->contract_status !== 'active') {
            throw new Exception('Can only generate payments for active contracts');
        }

        $payments = $contract->generatePaymentSchedule();
        $collectionPayments = [];

        foreach ($payments as $payment) {
            // This would integrate with the collection payment system
            $collectionPayments[] = [
                'contract_id' => $contract->id,
                'tenant_id' => $contract->tenant_id,
                'unit_id' => $contract->unit_id,
                'amount' => $payment['amount'],
                'due_date' => $payment['due_date'],
                'period_start' => $payment['period_start'],
                'period_end' => $payment['period_end'],
                'payment_type' => 'rent',
                'status' => 'pending',
            ];
        }

        return $collectionPayments;
    }

    /**
     * Check unit availability for contract period.
     */
    public function checkUnitAvailability(int $unitId, string $startDate, string $endDate, ?int $excludeContractId = null): bool
    {
        // Quick check for invalid unit ID
        if ($unitId <= 0) {
            return false;
        }

        try {
            $this->validateUnitAvailability($unitId, $startDate, $endDate, $excludeContractId);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get available units for a property within a date range.
     */
    public function getAvailableUnitsForPeriod(int $propertyId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $units = Unit::where('property_id', $propertyId)->get();

        return $units->map(function ($unit) use ($startDate, $endDate) {
            $isAvailable = true;

            if ($startDate && $endDate) {
                // Check if unit has any overlapping contracts
                $hasOverlap = UnitContract::where('unit_id', $unit->id)
                    ->whereIn('contract_status', ['active', 'renewed', 'draft'])
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->where(function ($q1) use ($startDate, $endDate) {
                            // Check all overlap conditions
                            $q1->where('start_date', '<=', $endDate)
                                ->where('end_date', '>=', $startDate);
                        });
                    })
                    ->exists();

                $isAvailable = ! $hasOverlap;
            }

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'rent_price' => $unit->rent_price,
                'is_available' => $isAvailable,
                'display_name' => $unit->name.
                    ' - '.number_format($unit->rent_price).' SAR'.
                    (! $isAvailable ? ' (Reserved for this period)' : ''),
            ];
        });
    }

    /**
     * Enhanced validation for unit availability with comprehensive checking.
     */
    protected function validateUnitAvailability(int $unitId, string $startDate, string $endDate, ?int $excludeContractId = null): void
    {
        // Validate unit ID is not 0
        if ($unitId <= 0) {
            throw new Exception('Invalid unit ID');
        }

        $unit = Unit::lockForUpdate()->findOrFail($unitId);

        // Check if unit exists and is not under maintenance
        if (isset($unit->status) && $unit->status === 'maintenance') {
            throw new Exception('Unit is currently under maintenance');
        }

        // Build query for overlapping contracts
        $query = UnitContract::lockForUpdate()
            ->where('unit_id', $unitId)
            ->whereIn('contract_status', ['active', 'renewed', 'draft'])
            ->where(function ($q) use ($startDate, $endDate) {
                // Comprehensive overlap detection
                $q->where(function ($q1) use ($startDate) {
                    // Case 1: New period starts within existing period
                    $q1->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $startDate);
                })->orWhere(function ($q2) use ($endDate) {
                    // Case 2: New period ends within existing period
                    $q2->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $endDate);
                })->orWhere(function ($q3) use ($startDate, $endDate) {
                    // Case 3: New period completely contains existing period
                    $q3->where('start_date', '>=', $startDate)
                        ->where('end_date', '<=', $endDate);
                })->orWhere(function ($q4) use ($startDate, $endDate) {
                    // Case 4: Existing period completely contains new period
                    $q4->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
            });

        // Exclude current contract when updating
        if ($excludeContractId) {
            $query->where('id', '!=', $excludeContractId);
        }

        $overlappingContract = $query->first();

        if ($overlappingContract) {
            $message = sprintf(
                'Unit is reserved for the requested period. Contract #%s exists from %s to %s',
                $overlappingContract->contract_number,
                $overlappingContract->start_date->format('Y-m-d'),
                $overlappingContract->end_date->format('Y-m-d')
            );
            throw new Exception($message);
        }
    }

    /**
     * Process security deposit.
     */
    public function processSecurityDeposit(int $contractId, string $action, array $data = []): array
    {
        $contract = UnitContract::findOrFail($contractId);
        $result = [];

        switch ($action) {
            case 'collect':
                $result = [
                    'action' => 'collected',
                    'amount' => $contract->security_deposit,
                    'date' => now(),
                    'status' => 'collected',
                ];
                break;

            case 'hold':
                $result = [
                    'action' => 'held',
                    'amount' => $contract->security_deposit,
                    'status' => 'held',
                ];
                break;

            case 'refund':
                $deductions = $data['deductions'] ?? 0;
                $refundAmount = $contract->security_deposit - $deductions;

                $result = [
                    'action' => 'refunded',
                    'original_amount' => $contract->security_deposit,
                    'deductions' => $deductions,
                    'refund_amount' => $refundAmount,
                    'date' => now(),
                    'status' => 'refunded',
                ];
                break;

            case 'deduct':
                $deductionAmount = $data['amount'] ?? 0;
                $reason = $data['reason'] ?? '';

                $result = [
                    'action' => 'deducted',
                    'deduction_amount' => $deductionAmount,
                    'reason' => $reason,
                    'remaining_deposit' => $contract->security_deposit - $deductionAmount,
                    'date' => now(),
                ];
                break;
        }

        // Log security deposit action
        activity()
            ->performedOn($contract)
            ->withProperties($result)
            ->log("Security deposit {$action}");

        return $result;
    }

    /**
     * Get contracts expiring soon.
     */
    public function getExpiringContracts(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return UnitContract::expiring($days)
            ->with(['tenant', 'unit', 'property'])
            ->get();
    }

    /**
     * Get tenant's contract history.
     */
    public function getTenantContractHistory(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return UnitContract::forTenant($tenantId)
            ->with(['unit', 'property'])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Get property occupancy summary.
     */
    public function getPropertyOccupancySummary(int $propertyId): array
    {
        $contracts = UnitContract::forProperty($propertyId)
            ->with(['unit', 'tenant'])
            ->get();

        $totalUnits = Unit::where('property_id', $propertyId)->count();
        $occupiedUnits = $contracts->where('contract_status', 'active')->count();

        return [
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacancy_rate' => $totalUnits > 0 ? (($totalUnits - $occupiedUnits) / $totalUnits) * 100 : 0,
            'occupancy_rate' => $totalUnits > 0 ? ($occupiedUnits / $totalUnits) * 100 : 0,
            'total_monthly_rent' => $contracts->where('contract_status', 'active')->sum('monthly_rent'),
            'contracts' => $contracts,
        ];
    }

    // ==========================================
    // Business Logic Methods (moved from Model)
    // ==========================================

    /**
     * Calculate total contract value.
     */
    public function calculateTotalContractValue(UnitContract $contract): float
    {
        return $contract->monthly_rent * $contract->duration_months;
    }

    /**
     * Calculate number of payments based on payment frequency.
     */
    public static function calculatePaymentsCount(int $durationMonths, string $paymentFrequency): int
    {
        return match ($paymentFrequency) {
            'monthly' => $durationMonths,
            'quarterly' => (int) ceil($durationMonths / 3),
            'semi_annual' => (int) ceil($durationMonths / 6),
            'annual' => (int) ceil($durationMonths / 12),
            default => $durationMonths,
        };
    }

    /**
     * Check if contract can be rescheduled.
     */
    public function canReschedule(UnitContract $contract): bool
    {
        // Contract must be active or draft
        if (! in_array($contract->contract_status, ['active', 'draft'])) {
            return false;
        }

        // Must have existing payments
        if (! $contract->collectionPayments()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Check if payments can be generated for contract.
     */
    public function canGeneratePayments(UnitContract $contract): bool
    {
        // Check if payments already exist
        if ($contract->collectionPayments()->exists()) {
            return false;
        }

        // Validate payments count is numeric and positive
        $paymentsCount = self::calculatePaymentsCount(
            $contract->duration_months ?? 0,
            $contract->payment_frequency ?? 'monthly'
        );

        if (! is_numeric($paymentsCount) || $paymentsCount <= 0) {
            return false;
        }

        // Validate required data
        return $contract->tenant_id &&
               $contract->unit_id &&
               $contract->monthly_rent > 0 &&
               $contract->start_date &&
               $contract->end_date;
    }

    /**
     * Calculate paid months count.
     */
    public function getPaidMonthsCount(UnitContract $contract): int
    {
        $paidPayments = $contract->collectionPayments()
            ->paid()
            ->orderBy('due_date_start')
            ->get();

        if ($paidPayments->isEmpty()) {
            return 0;
        }

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
     * Calculate remaining adjustable months.
     */
    public function getRemainingMonths(UnitContract $contract): int
    {
        $totalMonths = $contract->duration_months ?? 0;
        $paidMonths = $this->getPaidMonthsCount($contract);

        return max(0, $totalMonths - $paidMonths);
    }

    /**
     * Get paid payments count.
     */
    public function getPaidPaymentsCount(UnitContract $contract): int
    {
        return $contract->collectionPayments()
            ->paid()
            ->count();
    }

    /**
     * Get unpaid payments count.
     */
    public function getUnpaidPaymentsCount(UnitContract $contract): int
    {
        return $contract->collectionPayments()
            ->unpaid()
            ->count();
    }

    /**
     * Get paid payments.
     */
    public function getPaidPayments(UnitContract $contract): \Illuminate\Database\Eloquent\Collection
    {
        return $contract->collectionPayments()
            ->paid()
            ->orderBy('due_date_start')
            ->get();
    }

    /**
     * Get unpaid payments.
     */
    public function getUnpaidPayments(UnitContract $contract): \Illuminate\Database\Eloquent\Collection
    {
        return $contract->collectionPayments()
            ->unpaid()
            ->orderBy('due_date_start')
            ->get();
    }

    /**
     * Get last payment date.
     */
    public function getLastPaidDate(UnitContract $contract): ?Carbon
    {
        $lastPaidPayment = $contract->collectionPayments()
            ->paid()
            ->orderBy('due_date_end', 'desc')
            ->first();

        return $lastPaidPayment ? Carbon::parse($lastPaidPayment->due_date_end) : null;
    }

    /**
     * Get remaining days for contract.
     */
    public function getRemainingDays(UnitContract $contract): int
    {
        if ($contract->contract_status !== 'active' ||
            $contract->start_date > now() ||
            $contract->end_date < now()) {
            return 0;
        }

        return max(0, now()->diffInDays($contract->end_date, false));
    }

    /**
     * Check if contract can be renewed.
     */
    public function canRenew(UnitContract $contract): bool
    {
        // Can renew if contract is active
        if (! in_array($contract->contract_status, ['active'])) {
            return false;
        }

        // Cannot renew if already renewed
        if ($contract->contract_status === 'renewed') {
            return false;
        }

        return true;
    }

    /**
     * Calculate early termination penalty.
     */
    public function calculateEarlyTerminationPenalty(UnitContract $contract): float
    {
        // Calculate remaining months
        $remainingDays = $this->getRemainingDays($contract);
        $remainingMonths = ceil($remainingDays / 30);

        // Penalty = 2 months rent or remaining months (whichever is less)
        $penaltyMonths = min(2, $remainingMonths);

        return $contract->monthly_rent * $penaltyMonths;
    }

    /**
     * Check if contract can be terminated early.
     */
    public function canTerminateEarly(UnitContract $contract): bool
    {
        return $contract->contract_status === 'active' &&
               $contract->end_date > now();
    }

    /**
     * Get financial summary for contract.
     */
    public function getFinancialSummary(UnitContract $contract): array
    {
        $payments = $contract->collectionPayments()->get();
        $paidPayments = $payments->filter(fn ($p) => $p->collection_date !== null);
        $unpaidPayments = $payments->filter(fn ($p) => $p->collection_date === null);

        return [
            'total_contract_value' => $this->calculateTotalContractValue($contract),
            'total_payments' => $payments->count(),
            'paid_payments_count' => $paidPayments->count(),
            'unpaid_payments_count' => $unpaidPayments->count(),
            'total_paid_amount' => $paidPayments->sum('total_amount'),
            'total_pending_amount' => $unpaidPayments->sum('total_amount'),
            'late_fees_total' => $payments->sum('late_fee'),
            'security_deposit' => $contract->security_deposit,
            'collection_rate' => $payments->count() > 0
                ? round(($paidPayments->count() / $payments->count()) * 100, 2)
                : 0,
        ];
    }
}

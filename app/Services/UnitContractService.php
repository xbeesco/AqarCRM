<?php

namespace App\Services;

use App\Models\UnitContract;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UnitContractService
{
    /**
     * Create a new unit contract.
     */
    public function createContract(array $data): UnitContract
    {
        return DB::transaction(function () use ($data) {
            // Validate unit availability
            $this->validateUnitAvailability(
                $data['unit_id'],
                $data['start_date'],
                Carbon::parse($data['start_date'])->addMonths($data['duration_months'])
            );

            $data['created_by'] = Auth::id();
            
            $contract = UnitContract::create($data);
            
            // Log contract creation
            activity()
                ->performedOn($contract)
                ->withProperties($data)
                ->log('Unit contract created');

            return $contract;
        });
    }

    /**
     * Activate a draft contract and assign unit to tenant.
     */
    public function activateContract(int $contractId): UnitContract
    {
        return DB::transaction(function () use ($contractId) {
            $contract = UnitContract::findOrFail($contractId);

            // Validate contract can be activated
            if ($contract->contract_status !== 'draft') {
                throw new \Exception('Only draft contracts can be activated');
            }

            // Double-check unit availability
            $this->validateUnitAvailability(
                $contract->unit_id,
                $contract->start_date,
                $contract->end_date
            );

            // Activate the contract
            $contract->update([
                'contract_status' => 'active',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Mark unit as occupied (find occupied status ID)
            $occupiedStatusId = \App\Models\UnitStatus::where('slug', 'occupied')->first()?->id ?? 2;
            $contract->unit->update(['status_id' => $occupiedStatusId]);

            // Update tenant's current unit (update unit's current tenant)
            $contract->unit->update(['current_tenant_id' => $contract->tenant_id]);

            // Log activation
            activity()
                ->performedOn($contract)
                ->log('Unit contract activated and unit assigned to tenant');

            return $contract->fresh();
        });
    }

    /**
     * Terminate a contract and release unit.
     */
    public function terminateContract(int $contractId, string $reason): UnitContract
    {
        return DB::transaction(function () use ($contractId, $reason) {
            $contract = UnitContract::findOrFail($contractId);

            if ($contract->contract_status !== 'active') {
                throw new \Exception('Only active contracts can be terminated');
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
                'notes' => $contract->notes . "\n\nEarly termination penalty: SAR " . number_format($penalty, 2),
            ]);

            // Mark unit as available (find available status ID)
            $availableStatusId = \App\Models\UnitStatus::where('slug', 'available')->first()?->id ?? 1;
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

            if (!$oldContract->canRenew()) {
                throw new \Exception('Contract is not eligible for renewal');
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
                'notes' => $oldContract->notes . "\n\nRenewed with contract: " . $newContract->contract_number,
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
            throw new \Exception('Can only generate payments for active contracts');
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
    public function checkUnitAvailability(int $unitId, string $startDate, string $endDate): bool
    {
        try {
            $this->validateUnitAvailability($unitId, $startDate, $endDate);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate unit availability (throws exception if not available).
     */
    protected function validateUnitAvailability(int $unitId, string $startDate, string $endDate): void
    {
        $unit = Unit::findOrFail($unitId);

        // Check if unit exists and is not under maintenance
        $maintenanceStatusId = \App\Models\UnitStatus::where('slug', 'maintenance')->first()?->id;
        if ($unit->status_id === $maintenanceStatusId) {
            throw new \Exception('Unit is currently under maintenance');
        }

        // Check for overlapping contracts
        $overlappingContracts = UnitContract::where('unit_id', $unitId)
            ->where('contract_status', 'active')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->exists();

        if ($overlappingContracts) {
            throw new \Exception('Unit is not available for the specified period');
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
}
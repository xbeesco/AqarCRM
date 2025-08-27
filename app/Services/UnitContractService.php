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
                    'user_agent' => request()->userAgent()
                ]))
                ->log('Unit contract created with overlap validation');

            return $contract;
        }, 5); // 5 attempts for deadlock retry
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
    public function checkUnitAvailability(int $unitId, string $startDate, string $endDate, ?int $excludeContractId = null): bool
    {
        // Quick check for invalid unit ID
        if ($unitId <= 0) {
            return false;
        }
        
        try {
            $this->validateUnitAvailability($unitId, $startDate, $endDate, $excludeContractId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get available units for a property within a date range.
     */
    public function getAvailableUnitsForPeriod(int $propertyId, ?string $startDate = null, ?string $endDate = null): \Illuminate\Support\Collection
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
                
                $isAvailable = !$hasOverlap;
            }
            
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'rent_price' => $unit->rent_price,
                'is_available' => $isAvailable,
                'display_name' => $unit->name . 
                    ' - ' . number_format($unit->rent_price) . ' ريال' .
                    (!$isAvailable ? ' (محجوزة في هذه الفترة)' : '')
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
            throw new \Exception('معرف الوحدة غير صالح');
        }
        
        $unit = Unit::lockForUpdate()->findOrFail($unitId);

        // Check if unit exists and is not under maintenance
        if (isset($unit->status) && $unit->status === 'maintenance') {
            throw new \Exception('الوحدة تحت الصيانة حالياً');
        }

        // Build query for overlapping contracts
        $query = UnitContract::lockForUpdate()
            ->where('unit_id', $unitId)
            ->whereIn('contract_status', ['active', 'renewed', 'draft'])
            ->where(function ($q) use ($startDate, $endDate) {
                // Comprehensive overlap detection
                $q->where(function ($q1) use ($startDate, $endDate) {
                    // Case 1: New period starts within existing period
                    $q1->where('start_date', '<=', $startDate)
                       ->where('end_date', '>=', $startDate);
                })->orWhere(function ($q2) use ($startDate, $endDate) {
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
                'الوحدة محجوزة في الفترة المطلوبة. يوجد عقد رقم %s من %s إلى %s',
                $overlappingContract->contract_number,
                $overlappingContract->start_date->format('Y-m-d'),
                $overlappingContract->end_date->format('Y-m-d')
            );
            throw new \Exception($message);
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
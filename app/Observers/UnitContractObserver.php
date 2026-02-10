<?php

namespace App\Observers;

use Exception;
use App\Models\UnitContract;
use App\Services\PaymentGeneratorService;
use App\Services\UnitContractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UnitContractObserver
{
    protected UnitContractService $contractService;

    protected PaymentGeneratorService $paymentService;

    public function __construct(
        UnitContractService $contractService,
        PaymentGeneratorService $paymentService
    ) {
        $this->contractService = $contractService;
        $this->paymentService = $paymentService;
    }

    /**
     * Handle the UnitContract "creating" event.
     */
    public function creating(UnitContract $contract): void
    {
        // Always calculate end_date from start_date and duration_months for consistency
        // This ensures the dates are always synchronized regardless of what was passed
        if ($contract->start_date && $contract->duration_months) {
            $calculatedEndDate = Carbon::parse($contract->start_date)
                ->addMonths($contract->duration_months)
                ->subDay();
            $contract->end_date = $calculatedEndDate;
        }

        // Validate no overlap for new contracts
        if (in_array($contract->contract_status, ['active', 'renewed', 'draft'])) {
            $this->validateNoOverlap($contract);
        }

        // Final validation: ensure start_date < end_date
        if ($contract->start_date && $contract->end_date && $contract->start_date > $contract->end_date) {
            Log::warning('Suspicious contract date range detected', [
                'unit_id' => $contract->unit_id,
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);
            throw new Exception('Start date must be before end date');
        }
    }

    /**
     * Handle the UnitContract "updating" event.
     */
    public function updating(UnitContract $contract): void
    {
        // Recalculate end_date if dates or duration changed
        if ($contract->isDirty(['start_date', 'duration_months'])) {
            $contract->end_date = Carbon::parse($contract->start_date)
                ->addMonths($contract->duration_months)
                ->subDay();
        }

        // Validate no overlap when updating critical fields
        if ($contract->isDirty(['unit_id', 'start_date', 'end_date', 'contract_status'])) {
            if (in_array($contract->contract_status, ['active', 'draft'])) {
                $this->validateNoOverlap($contract);
            }
        }

        // Log status changes
        if ($contract->isDirty('contract_status')) {
            Log::info('Contract status changed', [
                'contract_id' => $contract->id,
                'old_status' => $contract->getOriginal('contract_status'),
                'new_status' => $contract->contract_status,
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the UnitContract "saving" event.
     */
    public function saving(UnitContract $contract): void
    {
        // Final validation before any save operation
        if ($contract->start_date && $contract->duration_months) {
            $startDate = Carbon::parse($contract->start_date);

            // Always recalculate end_date based on start_date and duration_months
            // This ensures consistency especially when Factory provides mismatched values
            $calculatedEndDate = $startDate->copy()->addMonths($contract->duration_months)->subDay();

            // If end_date was provided but doesn't match calculated, use calculated
            if (! $contract->end_date || Carbon::parse($contract->end_date)->ne($calculatedEndDate)) {
                $contract->end_date = $calculatedEndDate;
            }

            // Log duration changes if updating
            if ($contract->exists && $contract->isDirty('duration_months')) {
                Log::info('End date recalculated based on new duration', [
                    'contract_id' => $contract->id,
                    'duration_months' => $contract->duration_months,
                    'new_end_date' => $contract->end_date,
                ]);
            }
        }

        // Final validation: ensure start_date < end_date
        if ($contract->start_date && $contract->end_date) {
            $startDate = Carbon::parse($contract->start_date);
            $endDate = Carbon::parse($contract->end_date);

            if ($startDate->greaterThan($endDate)) {
                throw new Exception('Start date cannot be after end date');
            }
        }
    }

    /**
     * Validate that there's no overlap with other contracts.
     */
    protected function validateNoOverlap(UnitContract $contract): void
    {
        $query = UnitContract::where('unit_id', $contract->unit_id)
            ->whereIn('contract_status', ['active', 'draft'])  // renewed contracts don't block
            ->where(function ($q) use ($contract) {
                $q->where(function ($q1) use ($contract) {
                    // Start date falls within existing period
                    $q1->where('start_date', '<=', $contract->start_date)
                        ->where('end_date', '>=', $contract->start_date);
                })->orWhere(function ($q2) use ($contract) {
                    // End date falls within existing period
                    $q2->where('start_date', '<=', $contract->end_date)
                        ->where('end_date', '>=', $contract->end_date);
                })->orWhere(function ($q3) use ($contract) {
                    // New period contains existing period
                    $q3->where('start_date', '>=', $contract->start_date)
                        ->where('end_date', '<=', $contract->end_date);
                })->orWhere(function ($q4) use ($contract) {
                    // Existing period contains new period
                    $q4->where('start_date', '<=', $contract->start_date)
                        ->where('end_date', '>=', $contract->end_date);
                });
            });

        // Exclude self when updating
        if ($contract->exists) {
            $query->where('id', '!=', $contract->id);
        }

        $overlappingContract = $query->first();

        if ($overlappingContract) {
            // Log the overlap attempt
            Log::error('Contract overlap attempt blocked', [
                'unit_id' => $contract->unit_id,
                'new_contract' => [
                    'id' => $contract->id,
                    'start' => $contract->start_date,
                    'end' => $contract->end_date,
                    'status' => $contract->contract_status,
                ],
                'existing_contract' => [
                    'id' => $overlappingContract->id,
                    'number' => $overlappingContract->contract_number,
                    'start' => $overlappingContract->start_date,
                    'end' => $overlappingContract->end_date,
                    'status' => $overlappingContract->contract_status,
                ],
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
            ]);

            throw new Exception(sprintf(
                'Cannot save contract: Unit is reserved by contract #%s from %s to %s',
                $overlappingContract->contract_number,
                $overlappingContract->start_date->format('Y-m-d'),
                $overlappingContract->end_date->format('Y-m-d')
            ));
        }
    }

    /**
     * Handle the UnitContract "created" event.
     */
    public function created(UnitContract $contract): void
    {
        // Log successful creation
        Log::info('Contract created successfully', [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'unit_id' => $contract->unit_id,
            'tenant_id' => $contract->tenant_id,
            'period' => $contract->start_date.' to '.$contract->end_date,
        ]);

        // Auto-generate payments
        $this->generatePaymentsIfNeeded($contract, 'created');
    }

    /**
     * Handle the UnitContract "updated" event.
     */
    public function updated(UnitContract $contract): void
    {
        // Log successful update
        $changes = $contract->getChanges();
        unset($changes['updated_at']); // Remove noise

        if (! empty($changes)) {
            Log::info('Contract updated successfully', [
                'contract_id' => $contract->id,
                'changes' => $changes,
            ]);
        }

        // Generate payments when contract is activated or relevant data changes
        $relevantFields = ['duration_months', 'payment_frequency', 'start_date', 'monthly_rent', 'contract_status'];

        // Check if contract was activated (from non-active to active)
        $wasActivated = $contract->wasChanged('contract_status') &&
                       $contract->contract_status === 'active' &&
                       $contract->getOriginal('contract_status') !== 'active';

        if (($contract->wasChanged($relevantFields) || $wasActivated) && $contract->canGeneratePayments()) {
            $this->generatePaymentsIfNeeded($contract, 'updated');
        }
    }

    /**
     * Generate payments if contract is eligible
     */
    protected function generatePaymentsIfNeeded(UnitContract $contract, string $event): void
    {
        try {
            // Check if payments can be generated
            if (! $contract->canGeneratePayments()) {
                return;
            }

            // Check if contract is active
            if ($contract->contract_status !== 'active') {
                return;
            }

            // Generate payments
            $payments = $this->paymentService->generateTenantPayments($contract);
            $count = count($payments);

            // Log success
            Log::info("Auto-generated {$count} collection payments for contract {$contract->contract_number} on {$event}");

        } catch (Exception $e) {
            // Log error without stopping the process
            Log::warning("Failed to auto-generate payments for contract {$contract->contract_number}: ".$e->getMessage());
        }
    }
}

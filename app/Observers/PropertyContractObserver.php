<?php

namespace App\Observers;

use Exception;
use App\Models\PropertyContract;
use App\Services\PaymentGeneratorService;
use Illuminate\Support\Facades\Log;

class PropertyContractObserver
{
    protected PaymentGeneratorService $paymentService;

    public function __construct(PaymentGeneratorService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle the PropertyContract "created" event.
     */
    public function created(PropertyContract $contract): void
    {
        $this->generatePaymentsIfNeeded($contract, 'created');
    }

    /**
     * Handle the PropertyContract "updated" event.
     */
    public function updated(PropertyContract $contract): void
    {
        // Generate payments when contract is activated or relevant data changes
        $relevantFields = ['duration_months', 'payment_frequency', 'start_date', 'commission_rate', 'contract_status'];

        // Check if contract was activated
        $wasActivated = $contract->wasChanged('contract_status') &&
                       $contract->contract_status === 'active' &&
                       $contract->getOriginal('contract_status') !== 'active';

        if (($contract->wasChanged($relevantFields) || $wasActivated) && $contract->canGeneratePayments()) {
            $this->generatePaymentsIfNeeded($contract, 'updated');
        }
    }

    /**
     * Generate payments if contract is eligible.
     */
    protected function generatePaymentsIfNeeded(PropertyContract $contract, string $event): void
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
            $count = $this->paymentService->generateSupplyPaymentsForContract($contract);

            // Log success
            Log::info("Auto-generated {$count} supply payments for contract {$contract->contract_number} on {$event}");

        } catch (Exception $e) {
            // Log error without stopping the process
            Log::warning("Failed to auto-generate payments for contract {$contract->contract_number}: ".$e->getMessage());
        }
    }
}

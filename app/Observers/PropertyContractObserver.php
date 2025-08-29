<?php

namespace App\Observers;

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
        // Set contract to active if not specified
        if (!$contract->contract_status || $contract->contract_status === 'draft') {
            $contract->contract_status = 'active';
            $contract->saveQuietly(); // Save without triggering events again
        }
        
        $this->generatePaymentsIfNeeded($contract, 'created');
    }
    
    /**
     * Handle the PropertyContract "updated" event.
     */
    public function updated(PropertyContract $contract): void
    {
        // توليد الدفعات عند تفعيل العقد أو تغيير البيانات المؤثرة
        $relevantFields = ['duration_months', 'payment_frequency', 'start_date', 'commission_rate', 'contract_status'];
        
        // التحقق من تفعيل العقد (من غير نشط إلى نشط)
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
    protected function generatePaymentsIfNeeded(PropertyContract $contract, string $event): void
    {
        try {
            // التحقق من إمكانية توليد الدفعات
            if (!$contract->canGeneratePayments()) {
                return;
            }
            
            // التحقق من أن العقد نشط
            if ($contract->contract_status !== 'active') {
                return;
            }
            
            // توليد الدفعات
            $count = $this->paymentService->generateSupplyPaymentsForContract($contract);
            
            // تسجيل النجاح
            Log::info("Auto-generated {$count} supply payments for contract {$contract->contract_number} on {$event}");
                
        } catch (\Exception $e) {
            // تسجيل الخطأ دون إيقاف العملية
            Log::warning("Failed to auto-generate payments for contract {$contract->contract_number}: " . $e->getMessage());
        }
    }
}
<?php

namespace App\Observers;

use App\Models\UnitContract;
use App\Services\PaymentGeneratorService;
use Carbon\Carbon;

class UnitContractObserver
{
    protected $paymentService;
    
    public function __construct(PaymentGeneratorService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * Handle the UnitContract "created" event.
     */
    public function created(UnitContract $contract)
    {
        // توليد رقم العقد إذا لم يكن موجود
        if (empty($contract->contract_number)) {
            $contract->contract_number = $this->generateContractNumber($contract);
            $contract->saveQuietly();
        }
        
        // توليد الدفعات تلقائياً عند إنشاء العقد
        if ($contract->contract_status === 'active') {
            $this->paymentService->generateTenantPayments($contract);
        }
    }
    
    /**
     * Handle the UnitContract "updated" event.
     */
    public function updated(UnitContract $contract)
    {
        // إذا تم تفعيل العقد، نولد الدفعات
        if ($contract->isDirty('contract_status') && $contract->contract_status === 'active') {
            // التحقق من عدم وجود دفعات سابقة
            if ($contract->payments()->count() === 0) {
                $this->paymentService->generateTenantPayments($contract);
            }
        }
    }
    
    /**
     * توليد رقم العقد
     */
    private function generateContractNumber(UnitContract $contract)
    {
        $year = Carbon::now()->year;
        $month = Carbon::now()->format('m');
        
        $lastContract = UnitContract::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastContract ? intval(substr($lastContract->contract_number, -4)) + 1 : 1;
        
        return sprintf('UC-%s%s-%04d', $year, $month, $sequence);
    }
}
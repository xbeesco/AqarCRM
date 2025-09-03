<?php

namespace App\Services;

use App\Models\UnitContract;
use App\Models\PropertyContract;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\Setting;
use App\Services\PropertyContractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentGeneratorService
{
    /**
     * توليد دفعات التحصيل للمستأجرين
     */
    public function generateTenantPayments(UnitContract $contract): array
    {
        if (!$contract->monthly_rent || $contract->monthly_rent <= 0) {
            throw new \InvalidArgumentException('مبلغ الإيجار الشهري غير صحيح');
        }

        DB::beginTransaction();
        
        try {
            $payments = [];
            $startDate = Carbon::parse($contract->start_date);
            $endDate = Carbon::parse($contract->end_date);
            $frequency = $contract->payment_frequency ?? 'monthly';
            
            // حساب عدد الدفعات
            $paymentCount = $this->calculatePaymentCount($startDate, $endDate, $frequency);
            
            if ($paymentCount <= 0) {
                throw new \InvalidArgumentException('عدد الدفعات غير صحيح للفترة المحددة');
            }
            
            // المبلغ الأساسي للدفعة
            $baseAmount = $this->calculatePaymentAmount($contract->monthly_rent, $frequency);
            
            $currentDate = $startDate->copy();
            $paymentNumber = 1;
            
            while ($currentDate <= $endDate && $paymentNumber <= $paymentCount) {
                // حساب تاريخ نهاية الفترة
                $periodEnd = $this->calculatePeriodEnd($currentDate, $frequency);
                
                // التأكد من عدم تجاوز تاريخ انتهاء العقد
                if ($periodEnd > $endDate) {
                    $periodEnd = $endDate;
                    // حساب المبلغ بالتناسب للفترة الأخيرة
                    $daysInPeriod = $currentDate->diffInDays($periodEnd) + 1;
                    $fullPeriodDays = $this->getFullPeriodDays($frequency);
                    $baseAmount = ($contract->monthly_rent * ($daysInPeriod / 30));
                }
                
                // إنشاء الدفعة
                $payment = CollectionPayment::create([
                    'payment_number' => $this->generatePaymentNumber($contract, $paymentNumber),
                    'unit_contract_id' => $contract->id,
                    'unit_id' => $contract->unit_id,
                    'property_id' => $contract->property_id,
                    'tenant_id' => $contract->tenant_id,
                    'payment_status_id' => 2,  // Due - تستحق التحصيل 
                    'amount' => $baseAmount,
                    'late_fee' => 0,
                    'total_amount' => $baseAmount,
                    'due_date_start' => $currentDate->format('Y-m-d'),
                    'due_date_end' => $periodEnd->format('Y-m-d'),
                    'month_year' => $currentDate->format('Y-m'),
                ]);
                
                $payments[] = $payment;
                
                // الانتقال للفترة التالية
                $currentDate = $this->getNextPeriodStart($currentDate, $frequency);
                $paymentNumber++;
            }
            
            DB::commit();
            return $payments;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * توليد دفعة توريد واحدة للمالك بناءً على المبالغ المحصلة الفعلية
     * تستخدم هذه الدالة لحساب دفعة التوريد الشهرية بناءً على ما تم تحصيله فعلياً
     */
    public function generateOwnerPayments(PropertyContract $contract, $month = null): ?SupplyPayment
    {
        DB::beginTransaction();
        
        try {
            $targetMonth = $month ? Carbon::parse($month) : Carbon::now();
            
            // جمع كل دفعات التحصيل المدفوعة لهذا الشهر
            $collectedPayments = CollectionPayment::where('property_id', $contract->property_id)
                ->where('payment_status_id', 3) // paid
                ->whereYear('paid_date', $targetMonth->year)
                ->whereMonth('paid_date', $targetMonth->month)
                ->get();
            
            if ($collectedPayments->isEmpty()) {
                DB::rollBack();
                return null;
            }
            
            // حساب المجموع
            $totalCollected = $collectedPayments->sum('total_amount');
            
            // حساب العمولة
            $commissionAmount = $totalCollected * ($contract->commission_rate / 100);
            
            // حساب المصروفات للشهر
            $expenses = $this->calculateMonthlyExpenses($contract->property_id, $targetMonth);
            
            // المبلغ الصافي للمالك
            $netAmount = $totalCollected - $commissionAmount - $expenses;
            
            // إنشاء دفعة التوريد
            $payment = SupplyPayment::create([
                'payment_number' => $this->generateSupplyPaymentNumber($contract, $targetMonth),
                'property_contract_id' => $contract->id,
                'property_id' => $contract->property_id,
                'owner_id' => $contract->owner_id,
                'payment_status_id' => 1, // pending
                'gross_amount' => $totalCollected,
                'commission_amount' => $commissionAmount,
                'expenses_amount' => $expenses,
                'net_amount' => $netAmount,
                'payment_month' => $targetMonth->format('Y-m'),
                'due_date' => $targetMonth->copy()->day($contract->payment_day ?? 5)->format('Y-m-d'),
            ]);
            
            DB::commit();
            return $payment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * حساب عدد الدفعات حسب التكرار
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
     * حساب مبلغ الدفعة حسب التكرار
     */
    private function calculatePaymentAmount($monthlyRent, $frequency): float
    {
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);
        return $monthlyRent * $monthsPerPayment;
    }
    
    /**
     * حساب نهاية الفترة
     */
    private function calculatePeriodEnd($startDate, $frequency): Carbon
    {
        $date = $startDate->copy();
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);
        
        return $date->addMonths($monthsPerPayment)->subDay();
    }
    
    /**
     * الحصول على بداية الفترة التالية
     */
    private function getNextPeriodStart($currentDate, $frequency): Carbon
    {
        $date = $currentDate->copy();
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);
        
        return $date->addMonths($monthsPerPayment);
    }
    
    /**
     * عدد الأيام في الفترة الكاملة
     */
    private function getFullPeriodDays($frequency): int
    {
        $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);
        return $monthsPerPayment * 30; // تقريبياً 30 يوم للشهر
    }
    
    /**
     * توليد رقم الدفعة للمستأجر
     */
    private function generatePaymentNumber($contract, int $sequence): string
    {
        return sprintf('PAY-%s-%04d', $contract->contract_number, $sequence);
    }
    
    /**
     * توليد رقم دفعة التوريد للمالك
     */
    private function generateSupplyPaymentNumber($contract, Carbon $month): string
    {
        return sprintf('SUP-%s-%s', $contract->contract_number, $month->format('Ym'));
    }
    
    /**
     * حساب المصروفات الشهرية للعقار
     */
    private function calculateMonthlyExpenses(int $propertyId, Carbon $month): float
    {
        // حساب مصروفات العقار نفسه
        $propertyExpenses = \App\Models\Expense::where('subject_type', 'App\Models\Property')
            ->where('subject_id', $propertyId)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->sum('cost');
        
        // حساب مصروفات الوحدات التابعة للعقار
        $property = \App\Models\Property::find($propertyId);
        $unitsExpenses = 0;
        
        if ($property) {
            $unitIds = $property->units->pluck('id');
            $unitsExpenses = \App\Models\Expense::where('subject_type', 'App\Models\Unit')
                ->whereIn('subject_id', $unitIds)
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->sum('cost');
        }
        
        // حساب مصروفات الصيانة من جدول property_repairs
        $maintenanceExpenses = \App\Models\PropertyRepair::where('property_id', $propertyId)
            ->whereYear('maintenance_date', $month->year)
            ->whereMonth('maintenance_date', $month->month)
            ->sum('total_cost');
        
        return $propertyExpenses + $unitsExpenses + $maintenanceExpenses;
    }
    
    /**
     * توليد جدول دفعات التوريد المتوقعة لعقد المالك (للتخطيط المسبق)
     * تستخدم هذه الدالة عند إنشاء العقد لتوليد جميع الدفعات المتوقعة مقدماً
     */
    public function generateSupplyPaymentsForContract(PropertyContract $contract): int
    {
        // التحقق من وجود دفعات مولدة مسبقاً
        if ($contract->supplyPayments()->exists()) {
            $count = $contract->supplyPayments()->count();
            throw new \Exception("لا يمكن توليد دفعات جديدة - يوجد {$count} دفعة مولدة مسبقاً لهذا العقد");
        }
        
        // التحقق الشامل من صلاحية توليد الدفعات
        if (!$contract->canGeneratePayments()) {
            // تحديد سبب المشكلة بدقة
            if (!is_numeric($contract->payments_count) || $contract->payments_count <= 0) {
                throw new \Exception('عدد الدفعات غير صحيح - تحقق من بيانات العقد');
            }
            
            if (!$contract->isValidDurationForFrequency()) {
                throw new \Exception('مدة العقد لا تتوافق مع تكرار الدفع المحدد');
            }
            
            throw new \Exception('لا يمكن توليد دفعات لهذا العقد - تحقق من صحة البيانات');
        }
        
        // تحقق إضافي من صحة المدة والتكرار
        if (!$contract->isValidDurationForFrequency()) {
            throw new \Exception('مدة العقد لا تتوافق مع تكرار الدفع المحدد');
        }
        
        $paymentsToGenerate = $contract->payments_count;
        
        DB::beginTransaction();
        
        try {
            $payments = [];
            $currentDate = Carbon::parse($contract->start_date);
            $endDate = Carbon::parse($contract->end_date);
            
            for ($i = 1; $i <= $paymentsToGenerate; $i++) {
                // حساب نهاية الفترة
                $periodEnd = $this->calculatePeriodEnd($currentDate, $contract->payment_frequency);
                
                // التأكد من عدم تجاوز تاريخ انتهاء العقد
                if ($periodEnd > $endDate) {
                    $periodEnd = $endDate->copy();
                }
                
                $payments[] = $this->createSupplyPayment($contract, $i, $currentDate, $periodEnd);
                
                // الانتقال للفترة التالية
                $currentDate = $this->getNextPeriodStart($currentDate, $contract->payment_frequency);
                
                // إيقاف التوليد إذا تجاوزنا تاريخ النهاية
                if ($currentDate > $endDate) {
                    break;
                }
            }
            
            DB::commit();
            return count($payments);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * إنشاء دفعة توريد واحدة
     */
    private function createSupplyPayment(
        PropertyContract $contract, 
        int $paymentNumber, 
        Carbon $periodStart, 
        Carbon $periodEnd
    ): SupplyPayment {
        return SupplyPayment::create([
            'payment_number' => sprintf('SUP-%s-%03d', $contract->contract_number, $paymentNumber),
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
                'دفعة رقم %d من %d - %s', 
                $paymentNumber, 
                $contract->payments_count, 
                $this->getFrequencyLabel($contract->payment_frequency)
            ),
            'invoice_details' => [
                'contract_number' => $contract->contract_number,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'payment_frequency' => $contract->payment_frequency,
                'generated_at' => now()->toDateTimeString()
            ]
        ]);
    }
    
    
    /**
     * الحصول على تسمية التكرار بالعربية
     */
    private function getFrequencyLabel(string $frequency): string
    {
        return match($frequency) {
            'monthly' => 'شهري',
            'quarterly' => 'ربع سنوي',
            'semi_annually' => 'نصف سنوي',
            'annually' => 'سنوي',
            default => 'شهري'
        };
    }
    
    /**
     * الحصول على عدد أيام الاستحقاق من الإعدادات
     */
    private function getPaymentDueDays(): int
    {
        return (int) Setting::get('payment_due_days', 5);
    }
    
    /**
     * إعادة جدولة دفعات العقد مع مرونة كاملة
     * الدفعات المدفوعة تبقى كما هي، والجزء غير المدفوع يمكن تغييره بحرية
     */
    public function rescheduleContractPayments(
        UnitContract $contract,
        float $newMonthlyRent,
        int $additionalMonths,
        string $newFrequency
    ): array {
        // التحقق من صحة البيانات
        if ($additionalMonths <= 0) {
            throw new \InvalidArgumentException('عدد الأشهر الإضافية يجب أن يكون أكبر من صفر');
        }
        
        if ($newMonthlyRent <= 0) {
            throw new \InvalidArgumentException('قيمة الإيجار يجب أن تكون أكبر من صفر');
        }
        
        // التحقق من توافق المدة مع التكرار
        if (!PropertyContractService::isValidDuration($additionalMonths, $newFrequency)) {
            throw new \InvalidArgumentException('المدة الإضافية لا تتوافق مع تكرار الدفع المختار');
        }
        
        DB::beginTransaction();
        
        try {
            // 1. الحصول على آخر تاريخ مدفوع
            $lastPaidDate = $this->getLastPaidPeriodEnd($contract);
            
            // 2. تاريخ البداية للدفعات الجديدة
            $newStartDate = $lastPaidDate ? $lastPaidDate->copy()->addDay() : Carbon::parse($contract->start_date);
            
            // 3. حذف جميع الدفعات غير المدفوعة
            $deletedCount = $this->deleteUnpaidPayments($contract);
            
            // 4. توليد الدفعات الجديدة
            $newPayments = $this->generatePaymentsFromDate(
                $contract,
                $newStartDate,
                $additionalMonths,
                $newFrequency,
                $newMonthlyRent
            );
            
            // 5. حساب التاريخ الجديد لنهاية العقد
            $newEndDate = $newStartDate->copy()->addMonths($additionalMonths)->subDay();
            
            // 6. حساب إجمالي الأشهر الجديد
            $paidMonths = $this->calculatePaidMonths($contract);
            $totalMonths = $paidMonths + $additionalMonths;
            
            // 7. تحديث العقد
            $contract->update([
                'end_date' => $newEndDate,
                'duration_months' => $totalMonths,
                'monthly_rent' => $newMonthlyRent,
                'notes' => $contract->notes . "\n[" . now()->format('Y-m-d H:i') . "] تمت إعادة جدولة الدفعات - حذف {$deletedCount} دفعة وإضافة " . count($newPayments) . " دفعة جديدة"
            ]);
            
            DB::commit();
            
            return [
                'deleted_count' => $deletedCount,
                'new_payments' => $newPayments,
                'paid_months' => $paidMonths,
                'total_months' => $totalMonths,
                'new_end_date' => $newEndDate
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * الحصول على آخر تاريخ فترة مدفوعة
     */
    public function getLastPaidPeriodEnd(UnitContract $contract): ?Carbon
    {
        $lastPaidPayment = $contract->payments()
            ->paid()  // استخدام الـ scope الجديد بناءً على collection_date
            ->orderBy('due_date_end', 'desc')
            ->first();
            
        return $lastPaidPayment ? Carbon::parse($lastPaidPayment->due_date_end) : null;
    }
    
    /**
     * حذف جميع الدفعات غير المدفوعة
     */
    private function deleteUnpaidPayments(UnitContract $contract): int
    {
        return $contract->payments()
            ->unpaid()  // استخدام الـ scope الجديد بناءً على collection_date
            ->delete();
    }
    
    /**
     * حساب عدد الأشهر المدفوعة
     */
    private function calculatePaidMonths(UnitContract $contract): int
    {
        $paidPayments = $contract->payments()
            ->paid()  // استخدام الـ scope الجديد بناءً على collection_date
            ->get();
            
        $totalDays = 0;
        foreach ($paidPayments as $payment) {
            $start = Carbon::parse($payment->due_date_start);
            $end = Carbon::parse($payment->due_date_end);
            $totalDays += $start->diffInDays($end) + 1;
        }
        
        // تحويل الأيام إلى أشهر (تقريبياً 30 يوم للشهر)
        return intval($totalDays / 30);
    }
    
    /**
     * توليد دفعات من تاريخ محدد
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
        
        // حساب عدد الدفعات
        $paymentCount = PropertyContractService::calculatePaymentsCount($durationMonths, $frequency);
        
        // الحصول على آخر رقم دفعة موجود
        $lastPaymentNumber = $contract->payments()->count();
        
        for ($i = 1; $i <= $paymentCount; $i++) {
            // حساب نهاية الفترة
            $periodEnd = $this->calculatePeriodEnd($currentDate, $frequency);
            
            // التأكد من عدم تجاوز تاريخ النهاية
            if ($periodEnd > $endDate) {
                $periodEnd = $endDate->copy();
                
                // حساب المبلغ بالتناسب للفترة الأخيرة إذا لزم الأمر
                $daysInPeriod = $currentDate->diffInDays($periodEnd) + 1;
                $monthsInPeriod = $daysInPeriod / 30;
                $paymentAmount = $monthlyRent * $monthsInPeriod;
            } else {
                // المبلغ الكامل للفترة
                $monthsPerPayment = PropertyContractService::getMonthsPerPayment($frequency);
                $paymentAmount = $monthlyRent * $monthsPerPayment;
            }
            
            // إنشاء الدفعة
            $payment = CollectionPayment::create([
                'payment_number' => $this->generatePaymentNumber($contract, $lastPaymentNumber + $i),
                'unit_contract_id' => $contract->id,
                'unit_id' => $contract->unit_id,
                'property_id' => $contract->property_id,
                'tenant_id' => $contract->tenant_id,
                // تم إزالة collection_status - الحالة تُحسب ديناميكياً
                'amount' => $paymentAmount,
                'late_fee' => 0,
                'total_amount' => $paymentAmount,
                'due_date_start' => $currentDate->format('Y-m-d'),
                'due_date_end' => $periodEnd->format('Y-m-d'),
                'month_year' => $currentDate->format('Y-m'),
            ]);
            
            $payments[] = $payment;
            
            // الانتقال للفترة التالية
            $currentDate = $this->getNextPeriodStart($currentDate, $frequency);
            
            // إيقاف التوليد إذا تجاوزنا تاريخ النهاية
            if ($currentDate > $endDate) {
                break;
            }
        }
        
        return $payments;
    }
}
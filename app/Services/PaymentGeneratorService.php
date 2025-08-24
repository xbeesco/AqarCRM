<?php

namespace App\Services;

use App\Models\UnitContract;
use App\Models\PropertyContract;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentGeneratorService
{
    /**
     * توليد دفعات التحصيل للمستأجرين
     */
    public function generateTenantPayments(UnitContract $contract)
    {
        DB::beginTransaction();
        
        try {
            $payments = [];
            $startDate = Carbon::parse($contract->start_date);
            $endDate = Carbon::parse($contract->end_date);
            $frequency = $contract->payment_frequency ?? 'monthly'; // monthly, quarterly, semi_annual, annual
            
            // حساب عدد الدفعات
            $paymentCount = $this->calculatePaymentCount($startDate, $endDate, $frequency);
            
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
                    'payment_status_id' => 1, // pending
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
     * توليد دفعات التوريد للملاك
     */
    public function generateOwnerPayments(PropertyContract $contract, $month = null)
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
    private function calculatePaymentCount($startDate, $endDate, $frequency)
    {
        $months = $startDate->diffInMonths($endDate) + 1;
        
        return match($frequency) {
            'monthly' => $months,
            'quarterly' => ceil($months / 3),
            'semi_annual' => ceil($months / 6),
            'annual' => ceil($months / 12),
            default => $months
        };
    }
    
    /**
     * حساب مبلغ الدفعة حسب التكرار
     */
    private function calculatePaymentAmount($monthlyRent, $frequency)
    {
        return match($frequency) {
            'monthly' => $monthlyRent,
            'quarterly' => $monthlyRent * 3,
            'semi_annual' => $monthlyRent * 6,
            'annual' => $monthlyRent * 12,
            default => $monthlyRent
        };
    }
    
    /**
     * حساب نهاية الفترة
     */
    private function calculatePeriodEnd($startDate, $frequency)
    {
        $date = $startDate->copy();
        
        return match($frequency) {
            'monthly' => $date->addMonth()->subDay(),
            'quarterly' => $date->addMonths(3)->subDay(),
            'semi_annual' => $date->addMonths(6)->subDay(),
            'annual' => $date->addYear()->subDay(),
            default => $date->addMonth()->subDay()
        };
    }
    
    /**
     * الحصول على بداية الفترة التالية
     */
    private function getNextPeriodStart($currentDate, $frequency)
    {
        $date = $currentDate->copy();
        
        return match($frequency) {
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addMonths(3),
            'semi_annual' => $date->addMonths(6),
            'annual' => $date->addYear(),
            default => $date->addMonth()
        };
    }
    
    /**
     * عدد الأيام في الفترة الكاملة
     */
    private function getFullPeriodDays($frequency)
    {
        return match($frequency) {
            'monthly' => 30,
            'quarterly' => 90,
            'semi_annual' => 180,
            'annual' => 365,
            default => 30
        };
    }
    
    /**
     * توليد رقم الدفعة للمستأجر
     */
    private function generatePaymentNumber($contract, $sequence)
    {
        return sprintf('PAY-%s-%04d', $contract->contract_number, $sequence);
    }
    
    /**
     * توليد رقم دفعة التوريد للمالك
     */
    private function generateSupplyPaymentNumber($contract, $month)
    {
        return sprintf('SUP-%s-%s', $contract->contract_number, $month->format('Ym'));
    }
    
    /**
     * حساب المصروفات الشهرية للعقار
     */
    private function calculateMonthlyExpenses($propertyId, $month)
    {
        // هنا يمكن حساب المصروفات من جدول expenses
        // مؤقتاً نرجع 0
        return 0;
    }
}
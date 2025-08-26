<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\PropertyRepair;
use App\Models\Expense;
use Carbon\Carbon;

class PropertyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        // إجمالي العقارات الحقيقي من قاعدة البيانات
        $totalProperties = Property::count();
        
        // إجمالي الوحدات الحقيقي
        $totalUnits = Unit::count();
        
        // الوحدات المشغولة من العقود النشطة
        $occupiedUnits = UnitContract::where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->distinct('unit_id')
            ->count('unit_id');
        
        // نسبة الإشغال الفعلية
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
        
        // التحصيلات الشهرية الفعلية (المدفوعات المحصلة هذا الشهر)
        $monthlyCollections = CollectionPayment::whereBetween('paid_date', [$startOfMonth, $endOfMonth])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');
            
        // المستحقات الفعلية (المبالغ غير المدفوعة حتى اليوم)
        $pendingAmount = CollectionPayment::where('due_date_start', '<=', now())
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');
            
        // تكاليف الصيانة لهذا الشهر
        $monthlyMaintenanceCosts = PropertyRepair::whereBetween('completion_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'completed')
            ->sum('total_cost');
            
        // إضافة المصروفات الأخرى
        $monthlyExpenses = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('cost');
        
        // إجمالي التكاليف (صيانة + مصروفات)
        $totalMonthlyExpenses = $monthlyMaintenanceCosts + $monthlyExpenses;
        
        // التوريدات للملاك هذا الشهر
        $monthlySupplies = SupplyPayment::whereBetween('paid_date', [$startOfMonth, $endOfMonth])
            ->where('supply_status', 'collected')
            ->sum('net_amount');
        
        // صافي الدخل الشهري = التحصيلات - (التوريدات + التكاليف)
        $netMonthlyIncome = $monthlyCollections - ($monthlySupplies + $totalMonthlyExpenses);
        
        // حساب التغيير مقارنة بالشهر الماضي
        $lastMonthStart = $startOfMonth->copy()->subMonth();
        $lastMonthEnd = $endOfMonth->copy()->subMonth();
        $lastMonthCollections = CollectionPayment::whereBetween('paid_date', [$lastMonthStart, $lastMonthEnd])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');
            
        $collectionChange = $lastMonthCollections > 0 
            ? round((($monthlyCollections - $lastMonthCollections) / $lastMonthCollections) * 100, 1)
            : 0;

        return [
            Stat::make('إجمالي العقارات', $totalProperties)
                ->description('عقار مسجل في النظام')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart($this->getPropertyGrowthChart()),
                
            Stat::make('إجمالي الوحدات', $totalUnits)
                ->description($occupiedUnits . ' مشغولة / ' . ($totalUnits - $occupiedUnits) . ' شاغرة')
                ->descriptionIcon('heroicon-m-home')
                ->color('info'),
                
            Stat::make('نسبة الإشغال', $occupancyRate . '%')
                ->description('معدل الإشغال الحالي')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($occupancyRate > 70 ? 'success' : ($occupancyRate > 50 ? 'warning' : 'danger')),
                
            Stat::make('التحصيلات الشهرية', number_format($monthlyCollections, 0) . ' ريال')
                ->description(($collectionChange >= 0 ? '↑' : '↓') . ' ' . abs($collectionChange) . '% عن الشهر الماضي')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($monthlyCollections > 0 ? 'success' : 'gray')
                ->chart($this->getMonthlyCollectionsChart()),
                
            Stat::make('المستحقات المتأخرة', number_format($pendingAmount, 0) . ' ريال')
                ->description($this->getOverdueCount() . ' دفعة متأخرة')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pendingAmount > 0 ? 'danger' : 'success'),
                
            Stat::make('تكاليف الصيانة', number_format($totalMonthlyExpenses, 0) . ' ريال')
                ->description('صيانة ومصروفات هذا الشهر')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),
                
            Stat::make('صافي الدخل الشهري', number_format($netMonthlyIncome, 0) . ' ريال')
                ->description($netMonthlyIncome >= 0 ? 'ربح هذا الشهر' : 'خسارة هذا الشهر')
                ->descriptionIcon($netMonthlyIncome >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netMonthlyIncome >= 0 ? 'success' : 'danger')
                ->chart($this->getNetIncomeChart()),
        ];
    }
    
    private function getOverdueCount(): int
    {
        return CollectionPayment::where('due_date_start', '<', now()->subDays(30))
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->count();
    }
    
    private function getMonthlyCollectionsChart(): array
    {
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $amount = CollectionPayment::whereMonth('paid_date', $month->month)
                ->whereYear('paid_date', $month->year)
                ->whereHas('paymentStatus', function ($query) {
                    $query->where('is_paid_status', true);
                })
                ->sum('total_amount');
            $chart[] = round($amount / 1000); // بالآلاف للرسم البياني
        }
        return $chart;
    }
    
    private function getPropertyGrowthChart(): array
    {
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i)->endOfMonth();
            $count = Property::where('created_at', '<=', $date)->count();
            $chart[] = $count;
        }
        return $chart;
    }
    
    private function getNetIncomeChart(): array
    {
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            
            // التحصيلات
            $collections = CollectionPayment::whereBetween('paid_date', [$startOfMonth, $endOfMonth])
                ->whereHas('paymentStatus', function ($query) {
                    $query->where('is_paid_status', true);
                })
                ->sum('total_amount');
                
            // التوريدات
            $supplies = SupplyPayment::whereBetween('paid_date', [$startOfMonth, $endOfMonth])
                ->where('supply_status', 'collected')
                ->sum('net_amount');
                
            // المصروفات
            $expenses = PropertyRepair::whereBetween('completion_date', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->sum('total_cost');
                
            $expenses += Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->sum('cost');
                
            $netIncome = $collections - ($supplies + $expenses);
            $chart[] = round($netIncome / 1000); // بالآلاف للرسم البياني
        }
        return $chart;
    }
}
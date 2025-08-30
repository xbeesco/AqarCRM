<?php

namespace App\Filament\Widgets;

use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\UnitContract;
use App\Models\PropertyContract;
use App\Models\Property;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use App\Helpers\DateHelper;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        // استخدام DateHelper للحصول على التاريخ الحالي
        $currentDate = DateHelper::getCurrentDate();
        $today = $currentDate->copy()->startOfDay();
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();
        
        // حساب المفروض تحصيله اليوم
        $todayCollectionDue = CollectionPayment::whereDate('due_date_start', '<=', $today)
            ->whereDate('due_date_end', '>=', $today)
            ->whereIn('collection_status', ['due', 'postponed'])
            ->sum('amount');
            
        // حساب المفروض توريده اليوم
        $todaySupplyDue = SupplyPayment::whereDate('due_date', $today)
            ->where('supply_status', 'worth_collecting')
            ->sum('net_amount');
            
        // حساب ما تم تحصيله اليوم
        $todayCollected = CollectionPayment::whereDate('collection_date', $today)
            ->where('collection_status', 'collected')
            ->sum('amount');
            
        // حساب ما تم توريده اليوم
        $todaySupplied = SupplyPayment::whereDate('paid_date', $today)
            ->where('supply_status', 'collected')
            ->sum('net_amount');
            
        // عدد المستأجرين المفروض يدفعوا اليوم
        $tenantsWithPaymentDue = CollectionPayment::whereDate('due_date_start', '<=', $today)
            ->whereDate('due_date_end', '>=', $today)
            ->whereIn('collection_status', ['due', 'postponed'])
            ->distinct('tenant_id')
            ->count('tenant_id');
            
        // العقود التي ستنتهي اليوم
        $unitContractsEndingToday = UnitContract::where('contract_status', 'active')
            ->whereDate('end_date', $today)
            ->count();
            
        $propertyContractsEndingToday = PropertyContract::where('contract_status', 'active')
            ->whereDate('end_date', $today)
            ->count();
            
        $totalContractsEndingToday = $unitContractsEndingToday + $propertyContractsEndingToday;
        
        return [
            Stat::make('المفروض تحصيله اليوم', number_format($todayCollectionDue, 2) . ' ريال')
                ->description('من ' . $tenantsWithPaymentDue . ' مستأجر')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning')
                ->chart([7, 5, 10, 3, 15, 10, 12]),
                
            Stat::make('المفروض توريده اليوم', number_format($todaySupplyDue, 2) . ' ريال')
                ->description('للملاك')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('info')
                ->chart([2, 4, 6, 8, 10, 12, 14]),
                
            Stat::make('تم التحصيل اليوم', number_format($todayCollected, 2) . ' ريال')
                ->description($todayCollectionDue > 0 ? number_format(($todayCollected / $todayCollectionDue) * 100, 1) . '% من المستهدف' : 'لا يوجد مستهدف')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($todayCollected >= $todayCollectionDue ? 'success' : 'warning')
                ->chart([5, 10, 15, 20, 15, 10, 5]),
                
            Stat::make('تم التوريد اليوم', number_format($todaySupplied, 2) . ' ريال')
                ->description($todaySupplyDue > 0 ? number_format(($todaySupplied / $todaySupplyDue) * 100, 1) . '% من المستهدف' : 'لا يوجد مستهدف')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($todaySupplied >= $todaySupplyDue ? 'success' : 'danger')
                ->chart([3, 6, 9, 12, 15, 18, 21]),
                
            Stat::make('عقود تنتهي اليوم', $totalContractsEndingToday)
                ->description(
                    $totalContractsEndingToday > 0 
                        ? sprintf('%d عقد وحدات، %d عقد ملاك', $unitContractsEndingToday, $propertyContractsEndingToday)
                        : 'لا توجد عقود تنتهي اليوم'
                )
                ->descriptionIcon('heroicon-m-clock')
                ->color($totalContractsEndingToday > 0 ? 'danger' : 'success')
                ->chart([10, 12, 14, 16, 14, 12, 10]),
                
            Stat::make('مستأجرون يستحق عليهم السداد اليوم', $tenantsWithPaymentDue)
                ->description('عدد المستأجرين المطلوب منهم دفع الإيجار اليوم')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($tenantsWithPaymentDue > 0 ? 'warning' : 'success')
                ->chart([5, 8, 6, 9, 7, 10, 8]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 3;
    }
}
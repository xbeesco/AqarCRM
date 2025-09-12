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

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        // استخدام Carbon للحصول على التاريخ الحالي
        $currentDate = Carbon::now();
        $today = $currentDate->copy()->startOfDay();
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();
        
        // حساب المفروض تحصيله اليوم (مؤقتاً - مش محتاجينه دلوقتي )
        // $todayCollectionDue = CollectionPayment::dueForCollection()->sum('amount');
            
        // حساب المفروض توريده اليوم (مؤقتاً - مش محتاجينه برضو )
        // $todaySupplyDue = SupplyPayment::whereDate('due_date', $today)
        //     ->where('supply_status', 'worth_collecting')
        //     ->sum('net_amount');
            
        // حساب ما تم تحصيله اليوم
        $todayCollected = CollectionPayment::whereDate('collection_date', $today)
            ->sum('amount');
            
        // حساب ما تم توريده اليوم
        $todaySupplied = SupplyPayment::whereDate('paid_date', $today)
            ->where('supply_status', 'collected')
            ->sum('net_amount');
            
        // المتغيرات دي  مش  محتاجينها دلوقتي 
        // $tenantsWithPaymentDue = CollectionPayment::dueForCollection()
        //     ->distinct('tenant_id')
        //     ->count('tenant_id');
            
        // $unitContractsEndingToday = UnitContract::where('contract_status', 'active')
        //     ->whereDate('end_date', $today)
        //     ->count();
            
        // $propertyContractsEndingToday = PropertyContract::where('contract_status', 'active')
        //     ->whereDate('end_date', $today)
        //     ->count();
            
        // $totalContractsEndingToday = $unitContractsEndingToday + $propertyContractsEndingToday;
        
        return [
            // الإبقاء فقط على البطاقتين المطلوبتين
            /*
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
            */
                
            Stat::make('تم التحصيل اليوم', number_format($todayCollected, 2) . ' ريال')
                ->description('المبلغ المحصل اليوم')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('تم التوريد اليوم', number_format($todaySupplied, 2) . ' ريال')
                ->description('المبلغ المورد للملاك')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                     
        ];
    }
    
    protected function getColumns(): int
    {
        return 2;
    }
}
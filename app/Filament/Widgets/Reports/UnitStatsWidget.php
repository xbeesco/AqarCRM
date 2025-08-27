<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;

class UnitStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // إجمالي الوحدات في النظام
        $totalUnits = Unit::count();
        
        // الوحدات المؤجرة حالياً
        $occupiedUnits = UnitContract::where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->distinct('unit_id')
            ->count('unit_id');
        
        // الوحدات الشاغرة
        $vacantUnits = $totalUnits - $occupiedUnits;
        
        // نسبة الإشغال
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
        
        // متوسط سعر الإيجار
        $averageRent = Unit::avg('rent_price') ?? 0;
        
        // إجمالي الإيجار الشهري المتوقع
        $totalMonthlyRent = Unit::sum('rent_price');
        
        // الإيجار الشهري الفعلي من الوحدات المؤجرة
        $actualMonthlyRent = Unit::whereHas('contracts', function ($query) {
                $query->where('contract_status', 'active')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now());
            })
            ->sum('rent_price');
        
        // عدد العقود المنتهية قريباً (خلال 30 يوم)
        $expiringContracts = UnitContract::where('contract_status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->count();

        return [
            Stat::make('إجمالي الوحدات', $totalUnits)
                ->description('وحدة في النظام')
                ->descriptionIcon('heroicon-m-home')
                ->color('primary'),
                
            Stat::make('الوحدات المؤجرة', $occupiedUnits)
                ->description('وحدة مشغولة حالياً')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('الوحدات الشاغرة', $vacantUnits)
                ->description('وحدة متاحة للإيجار')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($vacantUnits > 0 ? 'warning' : 'success'),
                
            Stat::make('نسبة الإشغال', $occupancyRate . '%')
                ->description('معدل الإشغال الحالي')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($occupancyRate > 70 ? 'success' : ($occupancyRate > 50 ? 'warning' : 'danger')),
                
            Stat::make('عقود منتهية قريباً', $expiringContracts)
                ->description('خلال 30 يوم')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringContracts > 0 ? 'warning' : 'success'),
                
            Stat::make('فجوة الإيرادات', number_format($totalMonthlyRent - $actualMonthlyRent, 0) . ' ريال')
                ->description('خسارة من الوحدات الشاغرة')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($totalMonthlyRent - $actualMonthlyRent > 0 ? 'danger' : 'success'),
        ];
    }
}
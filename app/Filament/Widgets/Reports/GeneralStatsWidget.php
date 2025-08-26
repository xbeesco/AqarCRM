<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Property;
use App\Models\User;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Enums\UserType;

class GeneralStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // إجمالي العقارات
        $totalProperties = Property::count();
        
        // إجمالي الملاك
        $totalOwners = User::where('type', UserType::OWNER->value)->count();
        
        // إجمالي الوحدات
        $totalUnits = Unit::count();
        
        // الوحدات المشغولة
        $occupiedUnits = UnitContract::where('contract_status', 'active')
            ->distinct('unit_id')
            ->count('unit_id');
        
        // الوحدات الشاغرة
        $vacantUnits = $totalUnits - $occupiedUnits;
        
        // نسبة الإشغال
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        return [
            Stat::make('إجمالي العقارات', $totalProperties)
                ->description('عقار مسجل في النظام')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart([7, 5, 10, 3, 15, 4, 17]),
                
            Stat::make('إجمالي الملاك', $totalOwners)
                ->description('مالك نشط')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([3, 5, 2, 7, 8, 4, 9]),
                
            Stat::make('إجمالي الوحدات', $totalUnits)
                ->description($occupiedUnits . ' مشغولة / ' . $vacantUnits . ' شاغرة')
                ->descriptionIcon('heroicon-m-home')
                ->color('info')
                ->chart([12, 8, 15, 10, 18, 14, 20]),
                
            Stat::make('نسبة الإشغال', $occupancyRate . '%')
                ->description('من إجمالي الوحدات')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($occupancyRate > 70 ? 'success' : ($occupancyRate > 50 ? 'warning' : 'danger'))
                ->chart([65, 70, 75, 68, 72, $occupancyRate]),
        ];
    }
}
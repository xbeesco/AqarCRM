<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;

class PropertyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // إجمالي العقارات في النظام
        $totalProperties = Property::count();
        
        // إجمالي الوحدات في النظام
        $totalUnits = Unit::count();
        
        // الوحدات المشغولة حالياً
        $occupiedUnits = UnitContract::where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->distinct('unit_id')
            ->count('unit_id');
        
        // نسبة الإشغال العامة
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
        
        // إجمالي الإيرادات المحصلة (كل الوقت)
        $totalRevenue = CollectionPayment::whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');
            
        // المستحقات غير المدفوعة
        $pendingAmount = CollectionPayment::where('due_date_start', '<=', now())
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');
            
        // إجمالي تكاليف الصيانة (كل الوقت)
        $totalMaintenanceCosts = PropertyRepair::where('status', 'completed')
            ->sum('total_cost');
            
        // العقود النشطة حالياً
        $activeContracts = UnitContract::where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();

        return [
            Stat::make('إجمالي العقارات', $totalProperties)
                ->description('عقار مسجل في النظام')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary'),
                
            Stat::make('إجمالي الوحدات', $totalUnits)
                ->description($occupiedUnits . ' مشغولة / ' . ($totalUnits - $occupiedUnits) . ' شاغرة')
                ->descriptionIcon('heroicon-m-home')
                ->color('info'),
                
            Stat::make('نسبة الإشغال', $occupancyRate . '%')
                ->description('معدل الإشغال الحالي')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($occupancyRate > 70 ? 'success' : ($occupancyRate > 50 ? 'warning' : 'danger')),
                
            Stat::make('العقود النشطة', $activeContracts)
                ->description('عقد نشط حالياً')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('success'),
                
            Stat::make('إجمالي الإيرادات', number_format($totalRevenue, 0) . ' ريال')
                ->description('إجمالي المحصل')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('المستحقات', number_format($pendingAmount, 0) . ' ريال')
                ->description('غير محصلة')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pendingAmount > 0 ? 'warning' : 'success'),
                
            Stat::make('تكاليف الصيانة', number_format($totalMaintenanceCosts, 0) . ' ريال')
                ->description('إجمالي الصيانة')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),
                
            Stat::make('صافي الإيرادات', number_format($totalRevenue - $totalMaintenanceCosts, 0) . ' ريال')
                ->description('الإيرادات - المصروفات')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($totalRevenue - $totalMaintenanceCosts >= 0 ? 'success' : 'danger'),
        ];
    }
}
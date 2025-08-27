<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use Carbon\Carbon;

class FinancialStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        
        // التحصيلات الشهرية
        $monthlyCollections = CollectionPayment::whereBetween('paid_date', [$startOfMonth, $endOfMonth])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');
            
        // التحصيلات المستحقة
        $pendingCollections = CollectionPayment::where('due_date_start', '<=', now())
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');
            
        // التوريدات الشهرية
        $monthlySupplies = SupplyPayment::whereBetween('paid_date', [$startOfMonth, $endOfMonth])
            ->where('supply_status', 'collected')
            ->sum('net_amount');
            
        // التوريدات المعلقة
        $pendingSupplies = SupplyPayment::where('due_date', '<=', now())
            ->where('supply_status', 'pending')
            ->sum('net_amount');
        
        // المتأخرات (أكثر من 30 يوم)
        $overduePayments = CollectionPayment::where('due_date_start', '<', now()->subDays(30))
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');
            
        // عدد المدفوعات المتأخرة
        $overdueCount = CollectionPayment::where('due_date_start', '<', now()->subDays(30))
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->count();

        return [
            Stat::make('التحصيلات الشهرية', number_format($monthlyCollections) . ' ريال')
                ->description('تم تحصيلها هذا الشهر')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([20000, 25000, 18000, 30000, 28000, 35000, $monthlyCollections]),
                
            Stat::make('التحصيلات المستحقة', number_format($pendingCollections) . ' ريال')
                ->description('في انتظار التحصيل')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([15000, 18000, 20000, 16000, 22000, 19000, $pendingCollections]),
                
            Stat::make('التوريدات الشهرية', number_format($monthlySupplies) . ' ريال')
                ->description('تم توريدها للملاك')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
                ->chart([25000, 28000, 22000, 30000, 26000, 32000, $monthlySupplies]),
                
            Stat::make('المدفوعات المتأخرة', number_format($overduePayments) . ' ريال')
                ->description($overdueCount . ' دفعة متأخرة')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart([5000, 8000, 6000, 10000, 7000, 9000, $overduePayments]),
        ];
    }
}
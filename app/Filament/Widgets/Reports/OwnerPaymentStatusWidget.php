<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\SupplyPayment;
use App\Models\CollectionPayment;
use Carbon\Carbon;
use Livewire\Attributes\On;

class OwnerPaymentStatusWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;
    
    public ?int $property_id = null;
    public ?int $unit_id = null;
    public string $owner_status = 'all';
    public ?string $date_from = null;
    public ?string $date_to = null;

    #[On('owner-filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->property_id = $filters['property_id'] ?? null;
        $this->unit_id = $filters['unit_id'] ?? null;
        $this->owner_status = $filters['owner_status'] ?? 'all';
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
    }

    protected function getStats(): array
    {
        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfYear();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfYear();

        // بناء الاستعلام للدفعات المحصلة
        $collectionQuery = CollectionPayment::query()
            ->where('collection_status', 'collected')
            ->whereBetween('paid_date', [$dateFrom, $dateTo]);

        // بناء الاستعلام لدفعات التوريد
        $supplyQuery = SupplyPayment::query()
            ->whereBetween('paid_date', [$dateFrom, $dateTo]);

        // تطبيق الفلاتر
        if ($this->property_id) {
            $collectionQuery->where('property_id', $this->property_id);
            $supplyQuery->whereHas('owner', function($q) {
                $q->whereHas('properties', function($subQ) {
                    $subQ->where('id', $this->property_id);
                });
            });
        }

        if ($this->unit_id) {
            $collectionQuery->where('unit_id', $this->unit_id);
            $supplyQuery->whereHas('owner', function($q) {
                $q->whereHas('properties', function($subQ) {
                    $subQ->whereHas('units', function($unitQ) {
                        $unitQ->where('id', $this->unit_id);
                    });
                });
            });
        }

        // حساب الإحصائيات
        $totalCollection = (clone $collectionQuery)->sum('total_amount');
        $collectionCount = (clone $collectionQuery)->count();
        
        // حساب الرسوم الإدارية من متوسط نسب العقود أو 5% كقيمة افتراضية
        $avgCommissionRate = \App\Models\PropertyContract::query()
            ->when($this->property_id, function($q) {
                $q->where('property_id', $this->property_id);
            })
            ->avg('commission_rate') ?? 5.00;
        
        $managementFees = $totalCollection * ($avgCommissionRate / 100);
        
        // حساب المبلغ المستحق للملاك (بعد خصم الرسوم)
        $ownerDue = $totalCollection - $managementFees;
        
        // المبالغ المحولة للملاك فعلياً
        $paidToOwners = (clone $supplyQuery)->where('supply_status', 'collected')->sum('net_amount');
        $paidCount = (clone $supplyQuery)->where('supply_status', 'collected')->count();
        
        // المبالغ المعلقة (لم تحول للملاك بعد)
        $pendingToOwners = $ownerDue - $paidToOwners;
        
        // نسبة التحويل للملاك
        $transferRate = $ownerDue > 0 ? round(($paidToOwners / $ownerDue) * 100, 1) : 0;

        return [
            Stat::make('إجمالي التحصيل', number_format($totalCollection, 0))
                ->description($collectionCount . ' دفعة محصلة')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->chart([15, 20, 18, 22, 25, 28, 26])
                ->chartColor('success'),

            Stat::make('المستحق للملاك', number_format($ownerDue, 0))
                ->description('بعد خصم الرسوم الإدارية')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->icon('heroicon-o-user-group')
                ->chart([12, 16, 14, 18, 20, 22, 21])
                ->chartColor('info'),

            Stat::make('محول للملاك', number_format($paidToOwners, 0))
                ->description($paidCount . ' عملية تحويل')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->icon('heroicon-o-arrow-right')
                ->chart([8, 12, 10, 14, 16, 18, 17])
                ->chartColor('primary'),

            Stat::make('معلق للملاك', number_format($pendingToOwners, 0))
                ->description('في انتظار التحويل')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-o-clock')
                ->chart([4, 4, 4, 4, 4, 4, 4])
                ->chartColor('warning'),

            Stat::make('نسبة التحويل', $transferRate . '%')
                ->description($paidCount . ' من أصل ' . $collectionCount . ' دفعة')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($transferRate >= 80 ? 'success' : ($transferRate >= 50 ? 'warning' : 'danger'))
                ->icon('heroicon-o-chart-pie')
                ->chart([60, 65, 70, 72, 75, 78, $transferRate])
                ->chartColor($transferRate >= 80 ? 'success' : 'warning'),

            Stat::make('الرسوم الإدارية', number_format($managementFees, 0))
                ->description(number_format($avgCommissionRate, 1) . '% من التحصيل')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('secondary')
                ->icon('heroicon-o-building-office-2')
                ->chart([1, 2, 1, 2, 2, 3, 2])
                ->chartColor('secondary'),
        ];
    }

    protected function getColumns(): int
    {
        return 6;
    }
}
<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\Property;
use App\Models\Unit;
use Carbon\Carbon;
use Livewire\Attributes\On;

class OwnerStatsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
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

        // بناء الاستعلامات الأساسية
        $ownersQuery = User::where('type', 'owner');
        $propertiesQuery = Property::query();
        $collectionQuery = CollectionPayment::query();
        $supplyQuery = SupplyPayment::query();

        // تطبيق فلتر العقار
        if ($this->property_id) {
            $ownersQuery->whereHas('properties', function($q) {
                $q->where('id', $this->property_id);
            });
            $propertiesQuery->where('id', $this->property_id);
            $collectionQuery->where('property_id', $this->property_id);
            $supplyQuery->whereHas('owner', function($q) {
                $q->whereHas('properties', function($subQ) {
                    $subQ->where('id', $this->property_id);
                });
            });
        }

        // تطبيق فلتر الوحدة
        if ($this->unit_id) {
            $ownersQuery->whereHas('properties', function($q) {
                $q->whereHas('units', function($subQ) {
                    $subQ->where('id', $this->unit_id);
                });
            });
            $propertiesQuery->whereHas('units', function($q) {
                $q->where('id', $this->unit_id);
            });
            $collectionQuery->where('unit_id', $this->unit_id);
        }

        // تطبيق فلتر حالة المالك
        switch ($this->owner_status) {
            case 'active':
                $ownersQuery->whereHas('properties', function($q) {
                    $q->where('status_id', 1); // Available status
                });
                break;
            case 'inactive':
                $ownersQuery->whereDoesntHave('properties', function($q) {
                    $q->where('status_id', 1);
                });
                break;
        }

        // حساب الإحصائيات
        $totalOwners = $ownersQuery->count();
        
        $activeOwners = (clone $ownersQuery)->whereHas('properties', function($q) {
            $q->where('status_id', 1); // Available status
        })->count();

        $totalProperties = (clone $propertiesQuery)->count();
        
        $newOwnersThisMonth = User::where('type', 'owner')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // حساب إجمالي التحصيل من العقارات المفلترة
        $totalCollection = (clone $collectionQuery)
            ->where('collection_status', 'collected')
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('total_amount');

        // حساب المبالغ المحولة للملاك
        $paidToOwners = (clone $supplyQuery)
            ->where('supply_status', 'collected')
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('net_amount');

        return [
            Stat::make('إجمالي الملاك', $totalOwners)
                ->description('العدد الكلي للملاك')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->icon('heroicon-o-user-group')
                ->chart([8, 12, 10, 15, 18, 20, 22])
                ->chartColor('primary'),

            Stat::make('الملاك النشطون', $activeOwners)
                ->description(($totalOwners > 0 ? round(($activeOwners / $totalOwners) * 100, 1) : 0) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-users')
                ->chart([6, 8, 9, 12, 14, 16, 18])
                ->chartColor('success'),

            Stat::make('الملاك الجدد', $newOwnersThisMonth)
                ->description('هذا الشهر')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info')
                ->icon('heroicon-o-user-plus')
                ->chart([1, 2, 1, 3, 2, 4, 3])
                ->chartColor('info'),

            Stat::make('إجمالي العقارات', $totalProperties)
                ->description('عدد العقارات المسجلة')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning')
                ->icon('heroicon-o-building-office-2')
                ->chart([25, 28, 30, 32, 35, 38, 40])
                ->chartColor('warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Owner;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;
use Carbon\Carbon;
use Filament\Support\Colors\Color;

class OwnerStatsWidget extends BaseWidget
{
    public ?int $owner_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $report_type = 'summary';

    protected $listeners = [
        'owner-filters-updated' => 'updateFilters',
    ];

    public function updateFilters($filters): void
    {
        $this->owner_id = $filters['owner_id'] ?? null;
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
        $this->report_type = $filters['report_type'] ?? 'summary';
    }

    protected function getStats(): array
    {
        if (!$this->owner_id) {
            return [
                Stat::make('اختر مالك', 'يرجى اختيار مالك لعرض الإحصائيات')
                    ->description('')
                    ->color('gray'),
            ];
        }

        $owner = Owner::find($this->owner_id);
        if (!$owner) {
            return [];
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfMonth();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfMonth();

        // حساب إجمالي التحصيل
        $totalCollection = $this->calculateTotalCollection($owner, $dateFrom, $dateTo);
        
        // حساب النسبة الإدارية
        $managementPercentage = 10; // يمكن جعلها قابلة للتعديل
        $managementFee = $totalCollection * ($managementPercentage / 100);
        
        // حساب تكاليف الصيانة
        $maintenanceCosts = $this->calculateMaintenanceCosts($owner, $dateFrom, $dateTo);
        
        // حساب صافي الدخل
        $netIncome = $totalCollection - $managementFee - $maintenanceCosts;
        
        // حساب عدد العقارات والوحدات
        $propertiesCount = $owner->properties()->count();
        $totalUnits = $owner->properties()->withCount('units')->get()->sum('units_count');
        $occupiedUnits = $this->calculateOccupiedUnits($owner);
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0;

        // حساب المتوسط الشهري للدخل
        $monthsDiff = $dateFrom->diffInMonths($dateTo) + 1;
        $averageMonthlyIncome = $monthsDiff > 0 ? $netIncome / $monthsDiff : 0;

        return [
            Stat::make('إجمالي التحصيل', number_format($totalCollection, 2) . ' ريال')
                ->description('إجمالي المبلغ المحصل خلال الفترة')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart($this->getCollectionChart($owner, $dateFrom, $dateTo)),

            Stat::make('صافي الدخل', number_format($netIncome, 2) . ' ريال')
                ->description('بعد خصم النسبة الإدارية والصيانة')
                ->descriptionIcon($netIncome >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netIncome >= 0 ? 'success' : 'danger'),

            Stat::make('معدل الإشغال', $occupancyRate . '%')
                ->description($occupiedUnits . ' من ' . $totalUnits . ' وحدة مؤجرة')
                ->descriptionIcon('heroicon-m-home')
                ->color($this->getOccupancyColor($occupancyRate)),

            Stat::make('عدد العقارات', number_format($propertiesCount))
                ->description('إجمالي العقارات المملوكة')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('تكاليف الصيانة', number_format($maintenanceCosts, 2) . ' ريال')
                ->description('إجمالي مصروفات الصيانة')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),

            Stat::make('المتوسط الشهري', number_format($averageMonthlyIncome, 2) . ' ريال')
                ->description('متوسط صافي الدخل الشهري')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }

    private function calculateTotalCollection(Owner $owner, Carbon $dateFrom, Carbon $dateTo): float
    {
        return CollectionPayment::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');
    }

    private function calculateMaintenanceCosts(Owner $owner, Carbon $dateFrom, Carbon $dateTo): float
    {
        return PropertyRepair::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('completion_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['completed'])
            ->sum('actual_cost');
    }

    private function calculateOccupiedUnits(Owner $owner): int
    {
        return $owner->properties()
            ->withCount(['units' => function ($query) {
                $query->whereNotNull('current_tenant_id');
            }])
            ->get()
            ->sum('units_count');
    }

    private function getOccupancyColor(float $occupancyRate): string
    {
        if ($occupancyRate >= 90) return 'success';
        if ($occupancyRate >= 70) return 'warning';
        return 'danger';
    }

    private function getCollectionChart(Owner $owner, Carbon $dateFrom, Carbon $dateTo): array
    {
        $chart = [];
        $period = $dateFrom->copy();
        
        while ($period->lte($dateTo)) {
            $monthlyCollection = CollectionPayment::whereHas('property', function ($query) use ($owner) {
                    $query->where('owner_id', $owner->id);
                })
                ->whereYear('paid_date', $period->year)
                ->whereMonth('paid_date', $period->month)
                ->whereHas('paymentStatus', function ($query) {
                    $query->where('is_paid_status', true);
                })
                ->sum('total_amount');
                
            $chart[] = round($monthlyCollection, 2);
            $period->addMonth();
        }
        
        return $chart;
    }
}
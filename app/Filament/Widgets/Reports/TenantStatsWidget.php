<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tenant;
use App\Models\CollectionPayment;
use App\Models\UnitContract;
use Carbon\Carbon;
use Livewire\Attributes\On;

class TenantStatsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    public ?int $property_id = null;
    public ?int $unit_id = null;
    public string $tenant_status = 'all';
    public ?string $date_from = null;
    public ?string $date_to = null;

    #[On('tenant-filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->property_id = $filters['property_id'] ?? null;
        $this->unit_id = $filters['unit_id'] ?? null;
        $this->tenant_status = $filters['tenant_status'] ?? 'all';
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
    }

    protected function getStats(): array
    {
        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfYear();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfYear();

        // بناء الاستعلام الأساسي
        $tenantsQuery = Tenant::query();
        $paymentsQuery = CollectionPayment::query();
        $contractsQuery = UnitContract::query();

        // تطبيق فلتر العقار
        if ($this->property_id) {
            $tenantsQuery->whereHas('rentalContracts', function($q) {
                $q->where('property_id', $this->property_id);
            });
            $paymentsQuery->where('property_id', $this->property_id);
            $contractsQuery->where('property_id', $this->property_id);
        }

        // تطبيق فلتر الوحدة
        if ($this->unit_id) {
            $tenantsQuery->whereHas('rentalContracts', function($q) {
                $q->where('unit_id', $this->unit_id);
            });
            $paymentsQuery->where('unit_id', $this->unit_id);
            $contractsQuery->where('unit_id', $this->unit_id);
        }

        // تطبيق فلتر حالة المستأجر
        switch ($this->tenant_status) {
            case 'active':
                $tenantsQuery->whereHas('rentalContracts', function($q) {
                    $q->where('contract_status', 'active');
                });
                break;
            case 'expired':
                $tenantsQuery->whereHas('rentalContracts', function($q) {
                    $q->where('contract_status', 'expired');
                });
                break;
            case 'defaulter':
                $tenantsQuery->whereHas('paymentHistory', function($q) {
                    $q->where('collection_status', 'overdue');
                });
                break;
        }

        // حساب الإحصائيات
        $totalTenants = $tenantsQuery->count();
        
        $activeTenants = (clone $tenantsQuery)->whereHas('rentalContracts', function($q) {
            $q->where('contract_status', 'active');
        })->count();

        // إجمالي المدفوعات
        $totalPaid = (clone $paymentsQuery)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');

        // المستحقات المتبقية
        $outstandingBalance = (clone $paymentsQuery)
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');
        
        // المستأجرون الجدد هذا الشهر
        $newTenants = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // معدل الإشغال
        $totalUnits = \App\Models\Unit::count();
        $occupiedUnits = \App\Models\Unit::whereHas('activeContract')->count();
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        return [
            Stat::make('إجمالي المستأجرين', $totalTenants)
                ->description('العدد الكلي للمستأجرين')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->icon('heroicon-o-user-group')
                ->chart([12, 17, 14, 18, 20, 22, 19])
                ->chartColor('primary'),

            Stat::make('المستأجرون النشطون', $activeTenants)
                ->description(($totalTenants > 0 ? round(($activeTenants / $totalTenants) * 100, 1) : 0) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-users')
                ->chart([8, 10, 11, 12, 14, 13, 15])
                ->chartColor('success'),

            Stat::make('المستأجرون الجدد', $newTenants)
                ->description('هذا الشهر')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info')
                ->icon('heroicon-o-user-plus')
                ->chart([1, 2, 3, 2, 1, 3, 2])
                ->chartColor('info'),

            Stat::make('معدل الإشغال', $occupancyRate . '%')
                ->description('من إجمالي الوحدات')
                ->descriptionIcon('heroicon-m-home')
                ->color($occupancyRate >= 80 ? 'success' : ($occupancyRate >= 50 ? 'warning' : 'danger'))
                ->icon('heroicon-o-home-modern')
                ->chart([65, 70, 75, 72, 78, 80, $occupancyRate])
                ->chartColor($occupancyRate >= 80 ? 'success' : 'warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
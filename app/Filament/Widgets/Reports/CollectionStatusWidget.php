<?php

namespace App\Filament\Widgets\Reports;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\CollectionPayment;
use Carbon\Carbon;
use Livewire\Attributes\On;

class CollectionStatusWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;
    
    public ?int $property_id = null;
    public ?int $unit_id = null;
    public ?int $tenant_id = null;
    public string $tenant_status = 'all';
    public ?string $date_from = null;
    public ?string $date_to = null;

    #[On('tenant-filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->property_id = $filters['property_id'] ?? null;
        $this->unit_id = $filters['unit_id'] ?? null;
        $this->tenant_id = $filters['tenant_id'] ?? null;
        $this->tenant_status = $filters['tenant_status'] ?? 'all';
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
    }

    protected function getStats(): array
    {
        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfYear();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfYear();

        // بناء الاستعلام الأساسي
        $baseQuery = CollectionPayment::query()
            ->whereBetween('due_date_start', [$dateFrom, $dateTo]);

        // تطبيق الفلاتر
        if ($this->property_id) {
            $baseQuery->where('property_id', $this->property_id);
        }

        if ($this->unit_id) {
            $baseQuery->where('unit_id', $this->unit_id);
        }

        if ($this->tenant_id) {
            $baseQuery->where('tenant_id', $this->tenant_id);
        }

        // تطبيق فلتر حالة المستأجر
        if ($this->tenant_status !== 'all') {
            switch ($this->tenant_status) {
                case 'active':
                    $baseQuery->whereHas('unitContract', function($q) {
                        $q->where('contract_status', 'active');
                    });
                    break;
                case 'expired':
                    $baseQuery->whereHas('unitContract', function($q) {
                        $q->where('contract_status', 'expired');
                    });
                    break;
                case 'defaulter':
                    $baseQuery->where('collection_status', 'overdue');
                    break;
            }
        }

        // حساب الإحصائيات لكل حالة
        $collected = (clone $baseQuery)->where('collection_status', 'collected')->count();
        $collectedAmount = (clone $baseQuery)->where('collection_status', 'collected')->sum('total_amount');
        
        $due = (clone $baseQuery)->where('collection_status', 'due')->count();
        $dueAmount = (clone $baseQuery)->where('collection_status', 'due')->sum('total_amount');
        
        $overdue = (clone $baseQuery)->where('collection_status', 'overdue')->count();
        $overdueAmount = (clone $baseQuery)->where('collection_status', 'overdue')->sum('total_amount');
        
        $postponed = (clone $baseQuery)->where('collection_status', 'postponed')->count();
        $postponedAmount = (clone $baseQuery)->where('collection_status', 'postponed')->sum('total_amount');

        // حساب إجمالي عدد المدفوعات
        $totalPayments = $collected + $due + $overdue + $postponed;
        
        // حساب نسبة التحصيل
        $collectionRate = $totalPayments > 0 ? round(($collected / $totalPayments) * 100, 1) : 0;

        return [
            Stat::make('تم التحصيل', $collected)
                ->description(number_format($collectedAmount, 2) . ' ريال')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->chart([7, 4, 6, 5, 9, 3, 5])
                ->chartColor('success'),

            Stat::make('تستحق التحصيل', $due)
                ->description(number_format($dueAmount, 2) . ' ريال')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->icon('heroicon-o-clock')
                ->chart([3, 5, 4, 6, 3, 7, 4])
                ->chartColor('warning'),

            Stat::make('متأخرة', $overdue)
                ->description(number_format($overdueAmount, 2) . ' ريال')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->icon('heroicon-o-exclamation-circle')
                ->chart([2, 3, 5, 3, 2, 4, 3])
                ->chartColor('danger'),

            Stat::make('مؤجلة', $postponed)
                ->description(number_format($postponedAmount, 2) . ' ريال')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('info')
                ->icon('heroicon-o-pause-circle')
                ->chart([1, 2, 1, 3, 2, 1, 2])
                ->chartColor('info'),

            Stat::make('نسبة التحصيل', $collectionRate . '%')
                ->description($collected . ' من ' . $totalPayments . ' دفعة')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($collectionRate >= 80 ? 'success' : ($collectionRate >= 50 ? 'warning' : 'danger'))
                ->icon('heroicon-o-chart-pie')
                ->chart([$collected, $due, $overdue, $postponed])
                ->chartColor($collectionRate >= 80 ? 'success' : ($collectionRate >= 50 ? 'warning' : 'danger')),

            Stat::make('إجمالي المستحقات', number_format($dueAmount + $overdueAmount, 2) . ' ريال')
                ->description(($due + $overdue) . ' دفعة لم يتم تحصيلها')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color(($due + $overdue) > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-banknotes')
                ->chart([($due + $overdue), $collected])
                ->chartColor('warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
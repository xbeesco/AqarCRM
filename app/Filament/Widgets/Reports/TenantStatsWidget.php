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
    public ?int $tenant_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $report_type = 'summary';

    #[On('tenant-filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->tenant_id = $filters['tenant_id'] ?? null;
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
        $this->report_type = $filters['report_type'] ?? 'summary';
    }

    protected function getStats(): array
    {
        if (!$this->tenant_id) {
            return [
                Stat::make('يرجى اختيار مستأجر', '0')
                    ->description('اختر مستأجر من القائمة لعرض الإحصائيات')
                    ->icon('heroicon-o-user-group'),
            ];
        }

        $tenant = Tenant::find($this->tenant_id);
        if (!$tenant) {
            return [
                Stat::make('مستأجر غير موجود', '0')
                    ->description('لم يتم العثور على المستأجر المحدد')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfMonth();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfMonth();

        // حساب إجمالي المبلغ المدفوع
        $totalPaid = CollectionPayment::where('tenant_id', $this->tenant_id)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');

        // حساب المستحقات المتبقية
        $outstandingBalance = CollectionPayment::where('tenant_id', $this->tenant_id)
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');

        // حساب عدد المدفوعات المتأخرة
        $overduePayments = CollectionPayment::where('tenant_id', $this->tenant_id)
            ->where('collection_status', 'overdue')
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->count();

        // حساب معدل الالتزام بالدفع
        $totalPayments = CollectionPayment::where('tenant_id', $this->tenant_id)
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->count();

        $paidPayments = CollectionPayment::where('tenant_id', $this->tenant_id)
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->count();

        $complianceRate = $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100, 2) : 0;

        // عدد العقود النشطة
        $activeContracts = UnitContract::where('tenant_id', $this->tenant_id)
            ->where('contract_status', 'active')
            ->count();

        // متوسط تأخير المدفوعات
        $averageDelayDays = CollectionPayment::where('tenant_id', $this->tenant_id)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereNotNull('delay_duration')
            ->avg('delay_duration') ?? 0;

        return [
            Stat::make('إجمالي المدفوع', number_format($totalPaid, 2) . ' ريال')
                ->description('المبلغ الإجمالي المدفوع في الفترة')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('المستحقات المتبقية', number_format($outstandingBalance, 2) . ' ريال')
                ->description('المبلغ المستحق غير المدفوع')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($outstandingBalance > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-clock'),

            Stat::make('معدل الالتزام', $complianceRate . '%')
                ->description('نسبة المدفوعات في الوقت المحدد')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($complianceRate >= 80 ? 'success' : ($complianceRate >= 60 ? 'warning' : 'danger'))
                ->icon('heroicon-o-check-circle'),

            Stat::make('المدفوعات المتأخرة', $overduePayments)
                ->description('عدد المدفوعات التي تجاوزت موعدها')
                ->descriptionIcon('heroicon-m-clock')
                ->color($overduePayments > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-circle'),

            Stat::make('العقود النشطة', $activeContracts)
                ->description('عدد العقود النشطة حالياً')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($activeContracts > 0 ? 'success' : 'warning')
                ->icon('heroicon-o-document-check'),

            Stat::make('متوسط التأخير', round($averageDelayDays, 1) . ' يوم')
                ->description('متوسط أيام التأخير في المدفوعات')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($averageDelayDays <= 5 ? 'success' : ($averageDelayDays <= 15 ? 'warning' : 'danger'))
                ->icon('heroicon-o-calendar'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
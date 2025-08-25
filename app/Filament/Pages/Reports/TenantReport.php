<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Actions\Action;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\UnitContract;
use App\Models\PropertyRepair;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\DB;

class TenantReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'تقرير المستأجرين';
    protected static ?string $title = 'تقرير المستأجرين';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.reports.tenant-report';

    public ?int $tenant_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $report_type = 'summary';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('تصدير PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->iconPosition(IconPosition::Before)
                ->color('danger')
                ->action(fn () => $this->exportToPdf()),
                
            Action::make('export_excel')
                ->label('تصدير Excel')
                ->icon('heroicon-o-table-cells')
                ->iconPosition(IconPosition::Before)
                ->color('success')
                ->action(fn () => $this->exportToExcel()),
                
            Action::make('print')
                ->label('طباعة')
                ->icon('heroicon-o-printer')
                ->iconPosition(IconPosition::Before)
                ->color('gray')
                ->action(fn () => $this->printReport()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tenant_id')
                    ->label('المستأجر')
                    ->placeholder('اختر المستأجر')
                    ->options(function () {
                        return Tenant::with(['currentProperty'])
                            ->get()
                            ->mapWithKeys(function ($tenant) {
                                $property = $tenant->currentProperty ? ' - ' . $tenant->currentProperty->name : ' - غير مرتبط';
                                return [$tenant->id => $tenant->name . $property];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->updateWidgets()),

                DatePicker::make('date_from')
                    ->label('من تاريخ')
                    ->default(now()->startOfMonth())
                    ->live()
                    ->afterStateUpdated(fn () => $this->updateWidgets()),

                DatePicker::make('date_to')
                    ->label('إلى تاريخ')
                    ->default(now()->endOfMonth())
                    ->live()
                    ->afterStateUpdated(fn () => $this->updateWidgets()),

                Select::make('report_type')
                    ->label('نوع التقرير')
                    ->options([
                        'summary' => 'مختصر',
                        'detailed' => 'تفصيلي',
                        'payment_history' => 'سجل المدفوعات',
                        'contracts' => 'العقود',
                    ])
                    ->default('summary')
                    ->live()
                    ->afterStateUpdated(fn () => $this->updateWidgets()),
            ])
            ->columns(4);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\Reports\TenantStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\Reports\TenantPaymentsTableWidget::class,
            \App\Filament\Widgets\Reports\TenantContractsTableWidget::class,
        ];
    }

    private function updateWidgets(): void
    {
        $this->dispatch('tenant-filters-updated', [
            'tenant_id' => $this->tenant_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'report_type' => $this->report_type,
        ]);
    }

    public function getTenantData(): array
    {
        if (!$this->tenant_id) {
            return [];
        }

        $tenant = Tenant::with(['currentProperty', 'currentContract'])->find($this->tenant_id);
        if (!$tenant) {
            return [];
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfMonth();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfMonth();

        // معلومات الوحدة الحالية
        $currentUnit = null;
        $currentContract = UnitContract::where('tenant_id', $tenant->id)
            ->where('contract_status', 'active')
            ->with(['unit', 'property'])
            ->first();

        if ($currentContract) {
            $currentUnit = $currentContract->unit;
        }

        // حساب إجمالي المبلغ المدفوع
        $totalPaidAmount = CollectionPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');

        // حساب الرصيد المتبقي (المستحقات)
        $outstandingBalance = CollectionPayment::where('tenant_id', $tenant->id)
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');

        // حساب عدد المدفوعات المتأخرة
        $overduePayments = CollectionPayment::where('tenant_id', $tenant->id)
            ->where('collection_status', 'overdue')
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->count();

        // حساب متوسط تأخير المدفوعات
        $averageDelayDays = CollectionPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereNotNull('delay_duration')
            ->avg('delay_duration') ?? 0;

        // سجل المدفوعات للفترة المحددة
        $paymentHistory = CollectionPayment::where('tenant_id', $tenant->id)
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->with(['paymentStatus', 'paymentMethod', 'unit', 'property'])
            ->orderBy('due_date_start', 'desc')
            ->get();

        // سجل العقود
        $contractHistory = UnitContract::where('tenant_id', $tenant->id)
            ->with(['unit', 'property'])
            ->orderBy('start_date', 'desc')
            ->get();

        // عدد العقود النشطة
        $activeContractsCount = UnitContract::where('tenant_id', $tenant->id)
            ->where('contract_status', 'active')
            ->count();

        // إحصائيات المدفوعات
        $paymentStats = [
            'total_payments' => $paymentHistory->count(),
            'paid_payments' => $paymentHistory->filter(function ($payment) {
                return $payment->paymentStatus && $payment->paymentStatus->is_paid_status;
            })->count(),
            'pending_payments' => $paymentHistory->filter(function ($payment) {
                return $payment->paymentStatus && !$payment->paymentStatus->is_paid_status;
            })->count(),
            'overdue_payments' => $overduePayments,
        ];

        // حساب معدل الالتزام بالدفع
        $paymentComplianceRate = $paymentStats['total_payments'] > 0 
            ? round(($paymentStats['paid_payments'] / $paymentStats['total_payments']) * 100, 2) 
            : 0;

        // حساب إجمالي الرسوم المتأخرة
        $totalLateFees = CollectionPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('late_fee');

        // أحدث عقد منتهي الصلاحية
        $lastExpiredContract = UnitContract::where('tenant_id', $tenant->id)
            ->where('contract_status', 'expired')
            ->orderBy('end_date', 'desc')
            ->with(['unit', 'property'])
            ->first();

        // طلبات الصيانة المرتبطة بالمستأجر
        $maintenanceRequests = PropertyRepair::whereHas('unit', function ($query) use ($tenant) {
                $query->where('current_tenant_id', $tenant->id);
            })
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->with(['unit', 'property'])
            ->orderBy('created_at', 'desc')
            ->get();

        // إحصائيات مالية إضافية
        $financialStats = [
            'monthly_rent' => $currentContract ? $currentContract->monthly_rent : 0,
            'security_deposit' => $currentContract ? $currentContract->security_deposit : 0,
            'total_contract_value' => $currentContract ? ($currentContract->monthly_rent * $currentContract->duration_months) : 0,
            'remaining_contract_months' => $currentContract && $currentContract->end_date ? 
                max(0, Carbon::now()->diffInMonths(Carbon::parse($currentContract->end_date))) : 0,
        ];

        return [
            'tenant' => $tenant,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'currentUnit' => $currentUnit,
            'currentContract' => $currentContract,
            'totalPaidAmount' => $totalPaidAmount,
            'outstandingBalance' => $outstandingBalance,
            'paymentHistory' => $paymentHistory,
            'contractHistory' => $contractHistory,
            'activeContractsCount' => $activeContractsCount,
            'paymentStats' => $paymentStats,
            'paymentComplianceRate' => $paymentComplianceRate,
            'averageDelayDays' => round($averageDelayDays, 1),
            'totalLateFees' => $totalLateFees,
            'lastExpiredContract' => $lastExpiredContract,
            'maintenanceRequests' => $maintenanceRequests,
            'financialStats' => $financialStats,
        ];
    }

    protected function exportToPdf()
    {
        $data = $this->getTenantData();
        $this->js('alert("سيتم تنفيذ تصدير PDF قريباً")');
    }

    protected function exportToExcel()
    {
        $data = $this->getTenantData();
        $this->js('alert("سيتم تنفيذ تصدير Excel قريباً")');
    }

    protected function printReport()
    {
        $this->js('window.print()');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole(['admin', 'super_admin']) || $user->can('view_reports'));
    }

    public function mount(): void
    {
        $this->form->fill([
            'tenant_id' => $this->tenant_id,
            'date_from' => $this->date_from ?? now()->startOfMonth()->format('Y-m-d'),
            'date_to' => $this->date_to ?? now()->endOfMonth()->format('Y-m-d'),
            'report_type' => $this->report_type,
        ]);
    }
}
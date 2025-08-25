<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use App\Models\Owner;
use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\PropertyRepair;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\IconPosition;
use App\Enums\UserType;

class OwnerReport extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'تقرير الملاك';
    protected static ?string $title = 'تقرير الملاك';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 4;

    public ?int $owner_id = null;
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
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('owner_id')
                    ->label('المالك')
                    ->placeholder('اختر المالك')
                    ->options(function () {
                        return Owner::withCount('properties')
                            ->get()
                            ->mapWithKeys(function ($owner) {
                                return [$owner->id => $owner->name . ' (' . $owner->properties_count . ' عقار)'];
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
                        'financial' => 'مالي',
                        'properties' => 'العقارات',
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
            \App\Filament\Widgets\Reports\OwnerStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\Reports\PropertiesTableWidget::class,
            \App\Filament\Widgets\Reports\IncomeChartWidget::class,
        ];
    }

    private function updateWidgets(): void
    {
        $this->dispatch('owner-filters-updated', [
            'owner_id' => $this->owner_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'report_type' => $this->report_type,
        ]);
    }

    public function getOwnerData(): array
    {
        if (!$this->owner_id) {
            return [];
        }

        $owner = Owner::with(['properties', 'properties.units'])->find($this->owner_id);
        if (!$owner) {
            return [];
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfMonth();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfMonth();

        // حساب إجمالي التحصيل
        $totalCollection = CollectionPayment::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');

        // حساب المستحقات
        $outstandingPayments = CollectionPayment::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');

        // حساب النسبة الإدارية (افتراض 10%)
        $managementPercentage = 10;
        $managementFee = $totalCollection * ($managementPercentage / 100);

        // حساب تكاليف الصيانة
        $maintenanceCosts = PropertyRepair::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('completion_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['completed'])
            ->sum('actual_cost');

        // حساب صافي الدخل للمالك
        $netIncome = $totalCollection - $managementFee - $maintenanceCosts;

        // حساب المبالغ المحولة للمالك
        $transferredAmount = SupplyPayment::where('owner_id', $owner->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->where('payment_status', 'paid')
            ->sum('amount');

        // حساب الرصيد المتبقي
        $balance = $netIncome - $transferredAmount;

        // إحصائيات العقارات
        $propertiesCount = $owner->properties->count();
        $totalUnits = $owner->properties->sum('total_units');
        $occupiedUnits = $owner->properties->sum(function ($property) {
            return $property->units->where('current_tenant_id', '!=', null)->count();
        });
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0;

        // تفاصيل العقارات
        $propertiesDetails = $owner->properties->map(function ($property) use ($dateFrom, $dateTo) {
            $propertyUnits = $property->units->count();
            $propertyOccupied = $property->units->where('current_tenant_id', '!=', null)->count();
            $propertyRevenue = CollectionPayment::where('property_id', $property->id)
                ->whereBetween('paid_date', [$dateFrom, $dateTo])
                ->whereHas('paymentStatus', function ($query) {
                    $query->where('is_paid_status', true);
                })
                ->sum('total_amount');
            
            return [
                'id' => $property->id,
                'name' => $property->name,
                'total_units' => $propertyUnits,
                'occupied_units' => $propertyOccupied,
                'vacant_units' => $propertyUnits - $propertyOccupied,
                'occupancy_rate' => $propertyUnits > 0 ? round(($propertyOccupied / $propertyUnits) * 100, 2) : 0,
                'revenue' => $propertyRevenue,
            ];
        });

        // إحصائيات المدفوعات
        $paymentStats = [
            'total_collected' => $totalCollection,
            'total_outstanding' => $outstandingPayments,
            'collection_rate' => ($totalCollection + $outstandingPayments) > 0 
                ? round(($totalCollection / ($totalCollection + $outstandingPayments)) * 100, 2) 
                : 0,
        ];

        // إحصائيات الصيانة
        $maintenanceRequests = PropertyRepair::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        $completedMaintenance = PropertyRepair::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('completion_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->count();

        return [
            'owner' => $owner,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalCollection' => $totalCollection,
            'outstandingPayments' => $outstandingPayments,
            'managementFee' => $managementFee,
            'managementPercentage' => $managementPercentage,
            'maintenanceCosts' => $maintenanceCosts,
            'netIncome' => $netIncome,
            'transferredAmount' => $transferredAmount,
            'balance' => $balance,
            'propertiesCount' => $propertiesCount,
            'totalUnits' => $totalUnits,
            'occupiedUnits' => $occupiedUnits,
            'vacantUnits' => $vacantUnits,
            'occupancyRate' => $occupancyRate,
            'propertiesDetails' => $propertiesDetails,
            'paymentStats' => $paymentStats,
            'maintenanceRequests' => $maintenanceRequests,
            'completedMaintenance' => $completedMaintenance,
        ];
    }

    protected function exportToPdf()
    {
        $data = $this->getOwnerData();
        $this->js('alert("سيتم تنفيذ تصدير PDF قريباً")');
    }

    protected function exportToExcel()
    {
        $data = $this->getOwnerData();
        $this->js('alert("سيتم تنفيذ تصدير Excel قريباً")');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        
        // Check if user type can access reports
        $userType = UserType::tryFrom($user->type);
        if (!$userType) {
            return false;
        }
        
        // Allow admin types to access reports
        return in_array($userType, [
            UserType::SUPER_ADMIN,
            UserType::ADMIN,
            UserType::EMPLOYEE,
        ]);
    }

    public function mount(): void
    {
        $this->form->fill([
            'owner_id' => $this->owner_id,
            'date_from' => $this->date_from ?? now()->startOfMonth()->format('Y-m-d'),
            'date_to' => $this->date_to ?? now()->endOfMonth()->format('Y-m-d'),
            'report_type' => $this->report_type,
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getOwnerData(),
        ];
    }

    protected string $view = 'filament.pages.reports.owner-report';
}
<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use App\Models\Property;
use App\Models\Unit;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;
use App\Models\UnitContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\DB;
use App\Enums\UserType;

class PropertyReport extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'تقرير العقارات';
    protected static ?string $title = 'تقرير العقارات';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 2;

    public ?int $property_id = null;
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
            ->schema($this->getFormSchema())
            ->columns(4);
    }
    
    protected function getFormSchema(): array
    {
        return [
                Select::make('property_id')
                    ->label('العقار')
                    ->placeholder('اختر العقار')
                    ->options(function () {
                        $options = [];
                        $properties = Property::with('owner')->get();
                        
                        foreach ($properties as $property) {
                            $propertyName = (string) ($property->name ?: 'عقار #' . $property->id);
                            $ownerName = (string) (optional($property->owner)->name ?: 'بدون مالك');
                            $label = $propertyName . ' - ' . $ownerName;
                            
                            // التأكد من أن القيمة string وليست null
                            if (!empty($label)) {
                                $options[(string) $property->id] = $label;
                            }
                        }
                        
                        // إضافة خيار افتراضي إذا كانت القائمة فارغة
                        if (empty($options)) {
                            $options[''] = 'لا توجد عقارات متاحة';
                        }
                        
                        return $options;
                    })
                    // ->searchable() // معطل مؤقتاً للاختبار
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
                        'occupancy' => 'الإشغال',
                        'financial' => 'مالي',
                    ])
                    ->default('summary')
                    ->live()
                    ->afterStateUpdated(fn () => $this->updateWidgets()),
            ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\Reports\PropertyStatsWidget::class,
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
        $this->dispatch('property-filters-updated', [
            'property_id' => $this->property_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'report_type' => $this->report_type,
        ]);
    }

    public function getPropertyData(): array
    {
        if (!$this->property_id) {
            return [];
        }

        $property = Property::with(['owner', 'units', 'location'])->find($this->property_id);
        if (!$property) {
            return [];
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfMonth();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfMonth();

        // إحصائيات الوحدات
        $totalUnits = $property->units->count();
        
        // حساب الوحدات المشغولة من خلال العقود النشطة
        $occupiedUnits = UnitContract::whereIn('unit_id', $property->units->pluck('id'))
            ->where('contract_status', 'active')
            ->distinct('unit_id')
            ->count('unit_id');
            
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0;

        // حساب الإيرادات
        $totalRevenue = CollectionPayment::where('property_id', $property->id)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');

        // حساب المستحقات
        $outstandingPayments = CollectionPayment::where('property_id', $property->id)
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');

        // حساب تكاليف الصيانة
        $maintenanceCosts = PropertyRepair::where('property_id', $property->id)
            ->whereBetween('completion_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->sum('total_cost');

        // حساب عدد العقود النشطة
        $activeContracts = UnitContract::whereHas('unit', function ($query) use ($property) {
                $query->where('property_id', $property->id);
            })
            ->where('contract_status', 'active')
            ->count();

        // حساب متوسط سعر الإيجار
        $averageRent = $property->units->avg('rent_price') ?? 0;

        // حساب إجمالي الإيجار الشهري المتوقع
        $monthlyRentPotential = $property->units->sum('rent_price');
        
        // حساب الإيجار الشهري الفعلي من الوحدات المؤجرة
        $occupiedUnitIds = UnitContract::whereIn('unit_id', $property->units->pluck('id'))
            ->where('contract_status', 'active')
            ->pluck('unit_id');
        $actualMonthlyRent = $property->units->whereIn('id', $occupiedUnitIds)->sum('rent_price');

        return [
            'property' => $property,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalUnits' => $totalUnits,
            'occupiedUnits' => $occupiedUnits,
            'vacantUnits' => $vacantUnits,
            'occupancyRate' => $occupancyRate,
            'totalRevenue' => $totalRevenue,
            'outstandingPayments' => $outstandingPayments,
            'maintenanceCosts' => $maintenanceCosts,
            'netIncome' => $totalRevenue - $maintenanceCosts,
            'activeContracts' => $activeContracts,
            'averageRent' => $averageRent,
            'monthlyRentPotential' => $monthlyRentPotential,
            'actualMonthlyRent' => $actualMonthlyRent,
        ];
    }

    protected function exportToPdf()
    {
        $data = $this->getPropertyData();
        $this->js('alert("سيتم تنفيذ تصدير PDF قريباً")');
    }

    protected function exportToExcel()
    {
        $data = $this->getPropertyData();
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
            'property_id' => $this->property_id,
            'date_from' => $this->date_from ?? now()->startOfMonth()->format('Y-m-d'),
            'date_to' => $this->date_to ?? now()->endOfMonth()->format('Y-m-d'),
            'report_type' => $this->report_type,
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getPropertyData(),
        ];
    }

    protected string $view = 'filament.pages.reports.property-report';
}
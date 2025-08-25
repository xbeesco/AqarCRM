<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use App\Models\Unit;
use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\UnitContract;
use App\Models\PropertyRepair;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\IconPosition;
use App\Enums\UserType;

class UnitReport extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationLabel = 'تقرير الوحدات';
    protected static ?string $title = 'تقرير الوحدات';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 3;

    public ?int $unit_id = null;
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
            ->schema([
                Select::make('property_id')
                    ->label('العقار')
                    ->placeholder('اختر العقار')
                    ->options(function () {
                        return Property::pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->unit_id = null;
                        $this->updateWidgets();
                    }),

                Select::make('unit_id')
                    ->label('الوحدة')
                    ->placeholder('اختر الوحدة')
                    ->options(function () {
                        if (!$this->property_id) {
                            return [];
                        }
                        return Unit::where('property_id', $this->property_id)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->disabled(fn () => !$this->property_id)
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
                        'rental_history' => 'سجل الإيجارات',
                        'maintenance' => 'الصيانة',
                    ])
                    ->default('summary')
                    ->live()
                    ->afterStateUpdated(fn () => $this->updateWidgets())
                    ->columnSpan(2),
            ])
            ->columns(6);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // سيتم إضافة widgets لاحقاً
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // سيتم إضافة widgets لاحقاً
        ];
    }

    private function updateWidgets(): void
    {
        $this->dispatch('unit-filters-updated', [
            'unit_id' => $this->unit_id,
            'property_id' => $this->property_id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'report_type' => $this->report_type,
        ]);
    }

    public function getUnitData(): array
    {
        if (!$this->unit_id) {
            return [];
        }

        $unit = Unit::with(['property', 'unitCategory', 'contracts'])->find($this->unit_id);
        if (!$unit) {
            return [];
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfMonth();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfMonth();

        // العقد الحالي
        $currentContract = UnitContract::where('unit_id', $unit->id)
            ->where('contract_status', 'active')
            ->with('tenant')
            ->first();
        
        // حالة الوحدة والمستأجر الحالي
        $isOccupied = $currentContract !== null;
        $currentTenant = $currentContract ? $currentContract->tenant : null;

        // حساب الإيرادات
        $totalRevenue = CollectionPayment::where('unit_id', $unit->id)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');

        // المدفوعات المستحقة
        $outstandingPayments = CollectionPayment::where('unit_id', $unit->id)
            ->whereBetween('due_date_start', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');

        // تكاليف الصيانة
        $maintenanceCosts = PropertyRepair::where('unit_id', $unit->id)
            ->whereBetween('completion_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->sum('total_cost');

        // سجل الإيجارات
        $rentalHistory = UnitContract::where('unit_id', $unit->id)
            ->with('tenant')
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get();

        // حساب معدل الإشغال للفترة
        $totalDays = $dateFrom->diffInDays($dateTo);
        $occupiedDays = 0;
        
        $contracts = UnitContract::where('unit_id', $unit->id)
            ->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('start_date', [$dateFrom, $dateTo])
                    ->orWhereBetween('end_date', [$dateFrom, $dateTo])
                    ->orWhere(function ($q) use ($dateFrom, $dateTo) {
                        $q->where('start_date', '<=', $dateFrom)
                          ->where('end_date', '>=', $dateTo);
                    });
            })
            ->get();

        foreach ($contracts as $contract) {
            $contractStart = Carbon::parse($contract->start_date);
            $contractEnd = Carbon::parse($contract->end_date);
            
            $periodStart = $contractStart->greaterThan($dateFrom) ? $contractStart : $dateFrom;
            $periodEnd = $contractEnd->lessThan($dateTo) ? $contractEnd : $dateTo;
            
            $occupiedDays += $periodStart->diffInDays($periodEnd) + 1;
        }
        
        $occupancyRate = $totalDays > 0 ? round(($occupiedDays / $totalDays) * 100, 2) : 0;

        return [
            'unit' => $unit,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'isOccupied' => $isOccupied,
            'currentTenant' => $currentTenant,
            'currentContract' => $currentContract,
            'totalRevenue' => $totalRevenue,
            'outstandingPayments' => $outstandingPayments,
            'maintenanceCosts' => $maintenanceCosts,
            'netIncome' => $totalRevenue - $maintenanceCosts,
            'rentalHistory' => $rentalHistory,
            'occupancyRate' => $occupancyRate,
            'occupiedDays' => $occupiedDays,
            'totalDays' => $totalDays,
        ];
    }

    protected function exportToPdf()
    {
        $data = $this->getUnitData();
        $this->js('alert("سيتم تنفيذ تصدير PDF قريباً")');
    }

    protected function exportToExcel()
    {
        $data = $this->getUnitData();
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
            'unit_id' => $this->unit_id,
            'date_from' => $this->date_from ?? now()->startOfMonth()->format('Y-m-d'),
            'date_to' => $this->date_to ?? now()->endOfMonth()->format('Y-m-d'),
            'report_type' => $this->report_type,
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getUnitData(),
        ];
    }

    protected string $view = 'filament.pages.reports.unit-report';
}
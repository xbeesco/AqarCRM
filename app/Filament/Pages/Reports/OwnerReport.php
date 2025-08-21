<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Actions;
use App\Models\Owner;
use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\PropertyRepair;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\IconPosition;

class OwnerReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'تقرير المالك';
    protected static ?string $title = 'تقرير المالك';
    protected string $view = 'filament.pages.reports.owner-report';
    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';
    protected static ?int $navigationSort = 1;

    public ?int $owner_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $report_type = 'summary';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('تصدير PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->iconPosition(IconPosition::Before)
                ->color('danger')
                ->action(function () {
                    return $this->exportToPdf();
                }),
            Actions\Action::make('export_excel')
                ->label('تصدير Excel')
                ->icon('heroicon-o-table-cells')
                ->iconPosition(IconPosition::Before)
                ->color('success')
                ->action(function () {
                    return $this->exportToExcel();
                }),
            Actions\Action::make('print')
                ->label('طباعة')
                ->icon('heroicon-o-printer')
                ->iconPosition(IconPosition::Before)
                ->color('gray')
                ->action(function () {
                    return $this->printReport();
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('owner_id')
                    ->label('المالك')
                    ->placeholder('اختر المالك')
                    ->options(function () {
                        return Owner::pluck('name', 'id')->toArray();
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

        // حساب صافي الدخل
        $netIncome = $totalCollection - $managementFee - $maintenanceCosts;

        // حساب إحصائيات إضافية
        $propertiesCount = $owner->properties->count();
        $totalUnits = $owner->properties->sum('total_units');
        $occupiedUnits = $owner->properties->sum(function ($property) {
            return $property->units->where('current_tenant_id', '!=', null)->count();
        });
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0;

        return [
            'owner' => $owner,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalCollection' => $totalCollection,
            'managementFee' => $managementFee,
            'managementPercentage' => $managementPercentage,
            'maintenanceCosts' => $maintenanceCosts,
            'netIncome' => $netIncome,
            'propertiesCount' => $propertiesCount,
            'totalUnits' => $totalUnits,
            'occupiedUnits' => $occupiedUnits,
            'occupancyRate' => $occupancyRate,
        ];
    }

    protected function exportToPdf()
    {
        // تنفيذ تصدير PDF
        $data = $this->getOwnerData();
        // سيتم تنفيذ PDF export لاحقاً
        $this->js('alert("سيتم تنفيذ تصدير PDF قريباً")');
    }

    protected function exportToExcel()
    {
        // تنفيذ تصدير Excel
        $data = $this->getOwnerData();
        // سيتم تنفيذ Excel export لاحقاً
        $this->js('alert("سيتم تنفيذ تصدير Excel قريباً")');
    }

    protected function printReport()
    {
        // تنفيذ الطباعة
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
            'owner_id' => $this->owner_id,
            'date_from' => $this->date_from ?? now()->startOfMonth()->format('Y-m-d'),
            'date_to' => $this->date_to ?? now()->endOfMonth()->format('Y-m-d'),
            'report_type' => $this->report_type,
        ]);
    }
}
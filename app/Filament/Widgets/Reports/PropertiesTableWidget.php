<?php

namespace App\Filament\Widgets\Reports;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Owner;
use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;
use App\Models\UnitContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Colors\Color;

class PropertiesTableWidget extends BaseWidget
{
    protected static ?string $heading = 'تفاصيل العقارات';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getHeading(): ?string
    {
        return 'تفاصيل العقارات';
    }

    public ?int $property_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $report_type = 'summary';

    protected $listeners = [
        'property-filters-updated' => 'updateFilters',
    ];

    public function updateFilters($filters): void
    {
        $this->property_id = $filters['property_id'] ?? null;
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
        $this->report_type = $filters['report_type'] ?? 'summary';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->searchable()
            ->searchPlaceholder('ابحث في العقارات...')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم العقار')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('location.name_ar')
                    ->label('الموقع')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_units')
                    ->label('عدد الوحدات')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('occupied_units_count')
                    ->label('الوحدات المؤجرة')
                    ->getStateUsing(function (Property $record): int {
                        // حساب الوحدات المؤجرة من خلال العقود النشطة
                        return $record->units()
                            ->whereHas('contracts', function ($query) {
                                $query->where('contract_status', 'active')
                                    ->whereDate('start_date', '<=', now())
                                    ->whereDate('end_date', '>=', now());
                            })
                            ->count();
                    })
                    ->alignCenter()
                    ->color('success'),

                TextColumn::make('occupancy_rate')
                    ->label('معدل الإشغال')
                    ->getStateUsing(function (Property $record): string {
                        $totalUnits = $record->units()->count();
                        if ($totalUnits === 0) return '0%';
                        
                        // حساب الوحدات المؤجرة من خلال العقود النشطة
                        $occupiedUnits = $record->units()
                            ->whereHas('contracts', function ($query) {
                                $query->where('contract_status', 'active')
                                    ->whereDate('start_date', '<=', now())
                                    ->whereDate('end_date', '>=', now());
                            })
                            ->count();
                        $rate = round(($occupiedUnits / $totalUnits) * 100, 1);
                        return $rate . '%';
                    })
                    ->alignCenter()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_replace('%', '', $state) >= 90 => 'success',
                        str_replace('%', '', $state) >= 70 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('monthly_revenue')
                    ->label('الدخل الشهري')
                    ->getStateUsing(function (Property $record): string {
                        $monthlyRevenue = $this->calculatePropertyMonthlyRevenue($record);
                        return number_format($monthlyRevenue, 2) . ' ريال';
                    })
                    ->alignEnd()
                    ->color('success'),

                TextColumn::make('total_collection')
                    ->label('إجمالي التحصيل')
                    ->getStateUsing(function (Property $record): string {
                        $totalCollection = $this->calculatePropertyTotalCollection($record);
                        return number_format($totalCollection, 2) . ' ريال';
                    })
                    ->alignEnd()
                    ->color('primary'),

                TextColumn::make('maintenance_costs')
                    ->label('تكاليف الصيانة')
                    ->getStateUsing(function (Property $record): string {
                        $maintenanceCosts = $this->calculatePropertyMaintenanceCosts($record);
                        return number_format($maintenanceCosts, 2) . ' ريال';
                    })
                    ->alignEnd()
                    ->color('warning'),

                TextColumn::make('net_income')
                    ->label('صافي الدخل')
                    ->getStateUsing(function (Property $record): string {
                        $totalCollection = $this->calculatePropertyTotalCollection($record);
                        $maintenanceCosts = $this->calculatePropertyMaintenanceCosts($record);
                        $managementFee = $totalCollection * 0.1; // 10% management fee
                        $netIncome = $totalCollection - $managementFee - $maintenanceCosts;
                        return number_format($netIncome, 2) . ' ريال';
                    })
                    ->alignEnd()
                    ->badge()
                    ->color(function (Property $record): string {
                        $totalCollection = $this->calculatePropertyTotalCollection($record);
                        $maintenanceCosts = $this->calculatePropertyMaintenanceCosts($record);
                        $managementFee = $totalCollection * 0.1;
                        $netIncome = $totalCollection - $managementFee - $maintenanceCosts;
                        return $netIncome >= 0 ? 'success' : 'danger';
                    }),
            ])
            ->filters([
                // الفلاتر معطلة مؤقتاً لحل المشكلة
            ])
            ->recordActions([
                Action::make('view_report')
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->modalHeading(fn ($record) => 'تقرير العقار: ' . $record->name)
                    ->modalContent(fn ($record) => view('filament.reports.property-details', [
                        'property' => $record,
                        'stats' => $this->getPropertyStatistics($record),
                    ]))
                    ->modalWidth('7xl')
                    ->modalCancelActionLabel('إلغاء')
                    ->modalSubmitAction(
                        Action::make('print')
                            ->label('طباعة')
                            ->icon('heroicon-o-printer')
                            ->color('success')
                            ->action(fn () => null)
                            ->extraAttributes([
                                'onclick' => 'window.print(); return false;',
                            ])
                    ),
            ])
            ->toolbarActions([
                // يمكن إضافة إجراءات جماعية هنا
            ])
            ->emptyStateHeading('لا توجد عقارات')
            ->emptyStateDescription('لا توجد عقارات مسجلة في النظام حالياً')
            ->striped()
            ->defaultSort('name');
    }

    protected function getTableQuery(): Builder
    {
        $query = Property::query()->with(['location', 'units']);
        
        // إذا تم تحديد عقار معين، اعرض فقط هذا العقار
        if ($this->property_id) {
            $query->where('id', $this->property_id);
        }
        
        return $query;
    }

    private function calculatePropertyMonthlyRevenue(Property $property): float
    {
        // حساب الدخل الشهري من الوحدات المؤجرة حالياً
        return $property->units()
            ->whereHas('contracts', function ($query) {
                $query->where('contract_status', 'active')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now());
            })
            ->sum('rent_price');
    }

    private function calculatePropertyTotalCollection(Property $property): float
    {
        if (!$this->date_from || !$this->date_to) {
            return 0;
        }

        $dateFrom = Carbon::parse($this->date_from);
        $dateTo = Carbon::parse($this->date_to);

        return CollectionPayment::where('property_id', $property->id)
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');
    }

    private function calculatePropertyMaintenanceCosts(Property $property): float
    {
        if (!$this->date_from || !$this->date_to) {
            return 0;
        }

        $dateFrom = Carbon::parse($this->date_from);
        $dateTo = Carbon::parse($this->date_to);

        return PropertyRepair::where('property_id', $property->id)
            ->whereBetween('completion_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['completed'])
            ->sum('total_cost');
    }

    public function getTableRecordKey($record): string
    {
        return $record->getKey();
    }
    
    private function getPropertyStatistics(Property $property): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // إحصائيات الوحدات
        $totalUnits = $property->units()->count();
        $occupiedUnits = $property->units()
            ->whereHas('contracts', function ($query) {
                $query->where('contract_status', 'active')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now());
            })
            ->count();
        
        // الإيرادات
        $monthlyRevenue = $this->calculatePropertyMonthlyRevenue($property);
        $yearlyRevenue = CollectionPayment::where('property_id', $property->id)
            ->whereYear('paid_date', now()->year)
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');
            
        // المستحقات
        $pendingPayments = CollectionPayment::where('property_id', $property->id)
            ->where('due_date_start', '<=', now())
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');
            
        // الصيانة
        $maintenanceCosts = PropertyRepair::where('property_id', $property->id)
            ->whereYear('completion_date', now()->year)
            ->where('status', 'completed')
            ->sum('total_cost');
            
        // العقود النشطة
        $activeContracts = UnitContract::whereHas('unit', function ($query) use ($property) {
                $query->where('property_id', $property->id);
            })
            ->where('contract_status', 'active')
            ->count();
            
        return [
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $totalUnits - $occupiedUnits,
            'occupancy_rate' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0,
            'monthly_revenue' => $monthlyRevenue,
            'yearly_revenue' => $yearlyRevenue,
            'pending_payments' => $pendingPayments,
            'maintenance_costs' => $maintenanceCosts,
            'active_contracts' => $activeContracts,
            'net_income' => $yearlyRevenue - $maintenanceCosts,
        ];
    }
}
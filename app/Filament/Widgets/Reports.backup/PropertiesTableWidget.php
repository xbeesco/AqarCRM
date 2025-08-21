<?php

namespace App\Filament\Widgets\Reports;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Owner;
use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Support\Colors\Color;

class PropertiesTableWidget extends BaseWidget
{
    protected static ?string $heading = 'تفاصيل العقارات';
    protected int | string | array $columnSpan = 'full';

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

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
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
                        return $record->units()->whereNotNull('current_tenant_id')->count();
                    })
                    ->alignCenter()
                    ->color('success'),

                TextColumn::make('occupancy_rate')
                    ->label('معدل الإشغال')
                    ->getStateUsing(function (Property $record): string {
                        $totalUnits = $record->units()->count();
                        if ($totalUnits === 0) return '0%';
                        
                        $occupiedUnits = $record->units()->whereNotNull('current_tenant_id')->count();
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
                // يمكن إضافة فلاتر إضافية هنا إذا لزم الأمر
            ])
            ->actions([
                // يمكن إضافة إجراءات هنا
            ])
            ->bulkActions([
                // يمكن إضافة إجراءات جماعية هنا
            ])
            ->emptyStateHeading('لا توجد عقارات')
            ->emptyStateDescription('يرجى اختيار مالك لعرض عقاراته')
            ->striped()
            ->defaultSort('name');
    }

    protected function getTableQuery(): Builder
    {
        if (!$this->owner_id) {
            return Property::query()->where('id', 0); // Return empty query
        }

        return Property::query()
            ->where('owner_id', $this->owner_id)
            ->with(['location', 'units']);
    }

    private function calculatePropertyMonthlyRevenue(Property $property): float
    {
        return $property->units()
            ->whereNotNull('current_tenant_id')
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
            ->sum('actual_cost');
    }

    public function getTableRecordKey($record): string
    {
        return $record->getKey();
    }
}
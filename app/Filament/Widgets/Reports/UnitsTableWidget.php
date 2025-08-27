<?php

namespace App\Filament\Widgets\Reports;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Unit;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;
use App\Models\UnitContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Support\Colors\Color;

class UnitsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'تفاصيل الوحدات';
    
    protected int | string | array $columnSpan = 'full';
    
    public function getHeading(): ?string
    {
        return 'تفاصيل الوحدات';
    }

    public ?int $property_id = null;
    public ?int $unit_id = null;
    public string $unit_status = 'all';

    protected $listeners = [
        'unit-filters-updated' => 'updateFilters',
    ];

    public function updateFilters($filters): void
    {
        $this->property_id = $filters['property_id'] ?? null;
        $this->unit_id = $filters['unit_id'] ?? null;
        $this->unit_status = $filters['unit_status'] ?? 'all';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->searchable()
            ->searchPlaceholder('ابحث في الوحدات...')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('floor_number')
                    ->label('الطابق')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rooms_count')
                    ->label('عدد الغرف')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('area_sqm')
                    ->label('المساحة')
                    ->alignCenter()
                    ->suffix(' م²')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rent_price')
                    ->label('سعر الإيجار')
                    ->money('SAR')
                    ->alignEnd()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('current_tenant')
                    ->label('المستأجر الحالي')
                    ->getStateUsing(function (Unit $record): string {
                        $activeContract = $record->contracts()
                            ->where('contract_status', 'active')
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->with('tenant')
                            ->first();
                        
                        return $activeContract ? $activeContract->tenant->name : 'شاغرة';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'شاغرة' ? 'warning' : 'success'),

                TextColumn::make('contract_end')
                    ->label('انتهاء العقد')
                    ->getStateUsing(function (Unit $record): string {
                        $activeContract = $record->contracts()
                            ->where('contract_status', 'active')
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();
                        
                        if (!$activeContract) {
                            return '-';
                        }
                        
                        $endDate = Carbon::parse($activeContract->end_date);
                        $daysRemaining = now()->diffInDays($endDate, false);
                        
                        if ($daysRemaining < 30) {
                            return $endDate->format('Y/m/d') . ' (' . $daysRemaining . ' يوم)';
                        }
                        
                        return $endDate->format('Y/m/d');
                    })
                    ->color(function (Unit $record): string {
                        $activeContract = $record->contracts()
                            ->where('contract_status', 'active')
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->first();
                        
                        if (!$activeContract) {
                            return 'gray';
                        }
                        
                        $daysRemaining = now()->diffInDays($activeContract->end_date, false);
                        
                        if ($daysRemaining < 30) {
                            return 'danger';
                        } elseif ($daysRemaining < 60) {
                            return 'warning';
                        }
                        
                        return 'success';
                    }),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->getStateUsing(function (Unit $record): string {
                        $hasActiveContract = $record->contracts()
                            ->where('contract_status', 'active')
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->exists();
                        
                        return $hasActiveContract ? 'مؤجرة' : 'شاغرة';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'مؤجرة' => 'success',
                        'شاغرة' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                // الفلاتر معطلة مؤقتاً
            ])
            ->actions([
                Action::make('view_report')
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->modalHeading(fn ($record) => 'تقرير الوحدة: ' . $record->name)
                    ->modalContent(fn ($record) => view('filament.reports.unit-details', [
                        'unit' => $record,
                        'stats' => $this->getUnitStatistics($record),
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
            ->bulkActions([
                // يمكن إضافة إجراءات جماعية هنا
            ])
            ->emptyStateHeading('لا توجد وحدات')
            ->emptyStateDescription('لا توجد وحدات مسجلة في النظام حالياً')
            ->striped()
            ->defaultSort('name');
    }

    protected function getTableQuery(): Builder
    {
        $query = Unit::query()->with(['property', 'contracts.tenant', 'contracts']);
        
        // فلتر حسب العقار
        if ($this->property_id) {
            $query->where('property_id', $this->property_id);
        }
        
        // فلتر حسب الوحدة المحددة
        if ($this->unit_id) {
            $query->where('id', $this->unit_id);
        }
        
        // فلتر حسب حالة الوحدة
        if ($this->unit_status !== 'all') {
            if ($this->unit_status === 'occupied') {
                $query->whereHas('contracts', function ($q) {
                    $q->where('contract_status', 'active')
                        ->whereDate('start_date', '<=', now())
                        ->whereDate('end_date', '>=', now());
                });
            } elseif ($this->unit_status === 'vacant') {
                $query->whereDoesntHave('contracts', function ($q) {
                    $q->where('contract_status', 'active')
                        ->whereDate('start_date', '<=', now())
                        ->whereDate('end_date', '>=', now());
                });
            }
        }
        
        return $query;
    }

    public function getTableRecordKey($record): string
    {
        return $record->getKey();
    }
    
    private function getUnitStatistics(Unit $unit): array
    {
        // العقد النشط الحالي
        $activeContract = $unit->contracts()
            ->where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->with('tenant')
            ->first();
        
        // إجمالي الإيرادات من الوحدة
        $totalRevenue = CollectionPayment::where('unit_id', $unit->id)
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');
        
        // المستحقات غير المدفوعة
        $pendingPayments = CollectionPayment::where('unit_id', $unit->id)
            ->where('due_date_start', '<=', now())
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', false);
            })
            ->sum('total_amount');
        
        // تكاليف الصيانة للوحدة
        $maintenanceCosts = PropertyRepair::where('unit_id', $unit->id)
            ->where('status', 'completed')
            ->sum('total_cost');
        
        // عدد العقود السابقة
        $previousContracts = $unit->contracts()
            ->where('contract_status', 'completed')
            ->count();
        
        // متوسط مدة الإيجار
        $avgContractDuration = $unit->contracts()
            ->whereIn('contract_status', ['active', 'completed'])
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_days')
            ->value('avg_days');
        
        $avgContractMonths = $avgContractDuration ? round($avgContractDuration / 30) : 0;
        
        return [
            'property_name' => $unit->property->name,
            'floor_number' => $unit->floor_number,
            'rooms_count' => $unit->rooms_count,
            'area_sqm' => $unit->area_sqm,
            'rent_price' => $unit->rent_price,
            'is_occupied' => $activeContract !== null,
            'current_tenant' => $activeContract ? $activeContract->tenant->name : null,
            'contract_start' => $activeContract ? $activeContract->start_date : null,
            'contract_end' => $activeContract ? $activeContract->end_date : null,
            'total_revenue' => $totalRevenue,
            'pending_payments' => $pendingPayments,
            'maintenance_costs' => $maintenanceCosts,
            'net_income' => $totalRevenue - $maintenanceCosts,
            'previous_contracts' => $previousContracts,
            'avg_contract_months' => $avgContractMonths,
        ];
    }
}
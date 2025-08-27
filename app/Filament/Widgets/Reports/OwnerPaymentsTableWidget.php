<?php

namespace App\Filament\Widgets\Reports;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\SupplyPayment;
use App\Models\CollectionPayment;
use App\Models\User;
use App\Models\Property;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;

class OwnerPaymentsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'جدول مدفوعات الملاك';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public ?int $property_id = null;
    public ?int $unit_id = null;
    public string $owner_status = 'all';
    public ?string $date_from = null;
    public ?string $date_to = null;

    #[On('owner-filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->property_id = $filters['property_id'] ?? null;
        $this->unit_id = $filters['unit_id'] ?? null;
        $this->owner_status = $filters['owner_status'] ?? 'all';
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->searchable()
            ->searchPlaceholder('ابحث في مدفوعات الملاك...')
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم العملية')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('تم نسخ رقم العملية'),

                TextColumn::make('owner.name')
                    ->label('المالك')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->placeholder('غير محدد'),

                TextColumn::make('property_name')
                    ->label('العقار')
                    ->getStateUsing(function ($record) {
                        // جلب أسماء العقارات من خلال العلاقة
                        $properties = $record->owner?->properties()->pluck('name')->join(', ');
                        return $properties ?: 'غير محدد';
                    })
                    ->searchable(query: function ($query, string $search) {
                        // البحث في أسماء العقارات من خلال العلاقة
                        return $query->orWhereHas('owner', function ($q) use ($search) {
                            $q->whereHas('properties', function ($subQ) use ($search) {
                                $subQ->where('name', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->placeholder('غير محدد')
                    ->limit(30),

                TextColumn::make('gross_amount')
                    ->label('المبلغ الإجمالي')
                    ->money('SAR')
                    ->searchable()
                    ->alignEnd()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

                TextColumn::make('net_amount')
                    ->label('صافي المبلغ')
                    ->money('SAR')
                    ->searchable()
                    ->alignEnd()
                    ->weight(FontWeight::Bold)
                    ->color('success'),

                TextColumn::make('paid_date')
                    ->label('تاريخ التحويل')
                    ->date('Y/m/d')
                    ->searchable()
                    ->alignCenter()
                    ->sortable()
                    ->placeholder('لم يحول بعد'),

                BadgeColumn::make('supply_status')
                    ->label('حالة التوريد')
                    ->searchable(query: function ($query, string $search) {
                        // البحث بالعربي والإنجليزي
                        $statusMap = [
                            'محول' => 'collected',
                            'تم التحويل' => 'collected',
                            'محصل' => 'collected',
                            'تم التحصيل' => 'collected',
                            'معلق' => 'pending',
                            'انتظار' => 'pending',
                            'جاهز للتحصيل' => 'worth_collecting',
                            'جاهز' => 'worth_collecting',
                        ];
                        
                        // تحويل البحث إلى حروف صغيرة
                        $searchLower = mb_strtolower($search, 'UTF-8');
                        
                        // البحث في القيم العربية
                        $englishStatus = null;
                        foreach ($statusMap as $arabic => $english) {
                            if (str_contains(mb_strtolower($arabic, 'UTF-8'), $searchLower)) {
                                $englishStatus = $english;
                                break;
                            }
                        }
                        
                        if ($englishStatus) {
                            return $query->where('supply_status', $englishStatus);
                        }
                        
                        // البحث المباشر في القيم الإنجليزية
                        return $query->where('supply_status', 'like', "%{$search}%");
                    })
                    ->colors([
                        'success' => 'collected',
                        'warning' => 'worth_collecting',
                        'info' => 'pending',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'collected' => 'محول',
                            'worth_collecting' => 'جاهز للتحصيل',
                            'pending' => 'معلق',
                            default => 'غير محدد'
                        };
                    })
                    ->alignCenter(),

                TextColumn::make('commission_amount')
                    ->label('العمولة')
                    ->money('SAR')
                    ->alignEnd()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deductions_total')
                    ->label('إجمالي الخصومات')
                    ->getStateUsing(function ($record) {
                        return $record->commission_amount + $record->maintenance_deduction + $record->other_deductions;
                    })
                    ->money('SAR')
                    ->alignEnd()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bank_transfer_reference')
                    ->label('مرجع البنك')
                    ->searchable()
                    ->placeholder('غير محدد')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->searchable()
                    ->placeholder('لا توجد')
                    ->limit(30)
                    ->tooltip(function ($state) {
                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                Action::make('view_report')
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'تقرير دفعة المالك: ' . ($record->payment_number ?: '#' . $record->id))
                    ->modalContent(fn ($record) => view('filament.reports.owner-comprehensive-report', [
                        'owner' => $record->owner,
                        'stats' => $this->getOwnerFullStatistics($record),
                        'recentPayments' => $this->getOwnerRecentPayments($record->owner),
                        'dateRange' => [
                            'from' => $this->date_from ?? now()->subYear()->format('Y-m-d'),
                            'to' => $this->date_to ?? now()->format('Y-m-d')
                        ]
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
            ->defaultSort('paid_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->paginationPageOptions([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->poll('60s')
            ->deferLoading()
            ->emptyStateHeading('لا توجد مدفوعات للملاك')
            ->emptyStateDescription('لا توجد مدفوعات تطابق معايير البحث المحددة.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation|null
    {
        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfYear();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfYear();

        $query = SupplyPayment::query()
            ->with(['owner', 'owner.properties'])
            ->whereBetween('paid_date', [$dateFrom, $dateTo]);

        // Apply property filter through owner relationship
        if ($this->property_id) {
            $query->whereHas('owner', function($q) {
                $q->whereHas('properties', function($subQ) {
                    $subQ->where('id', $this->property_id);
                });
            });
        }

        // Apply unit filter through owner relationship
        if ($this->unit_id) {
            $query->whereHas('owner', function($q) {
                $q->whereHas('properties', function($subQ) {
                    $subQ->whereHas('units', function($unitQ) {
                        $unitQ->where('id', $this->unit_id);
                    });
                });
            });
        }

        // Apply owner status filter
        if ($this->owner_status !== 'all') {
            switch ($this->owner_status) {
                case 'active':
                    $query->whereHas('owner', function($q) {
                        $q->whereHas('properties', function($subQ) {
                            $subQ->where('status_id', 1);
                        });
                    });
                    break;
                case 'inactive':
                    $query->whereHas('owner', function($q) {
                        $q->whereDoesntHave('properties', function($subQ) {
                            $subQ->where('status_id', 1);
                        });
                    });
                    break;
            }
        }

        return $query;
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function getTableHeading(): ?string
    {
        $heading = 'سجل مدفوعات الملاك';
        
        if ($this->date_from || $this->date_to) {
            $dateFrom = $this->date_from ? Carbon::parse($this->date_from)->format('Y/m/d') : 'البداية';
            $dateTo = $this->date_to ? Carbon::parse($this->date_to)->format('Y/m/d') : 'النهاية';
            $heading .= " - من {$dateFrom} إلى {$dateTo}";
        }
        
        return $heading;
    }

    private function getOwnerRecentPayments($owner)
    {
        if (!$owner) return collect();
        
        return SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'collected')
            ->latest('paid_date')
            ->limit(5)
            ->get();
    }

    private function getOwnerFullStatistics($payment): array
    {
        $owner = $payment->owner;
        if (!$owner) {
            return $this->getEmptyStatistics();
        }

        // تحميل العلاقات
        $owner->load(['properties', 'properties.units']);
        
        // إحصائيات العقارات
        $propertiesCount = $owner->properties->count();
        $totalUnits = $owner->properties->sum(function($property) {
            return $property->units->count();
        });
        $occupiedUnits = $owner->properties->sum(function($property) {
            return $property->units->where('status', 'occupied')->count();
        });
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        // إحصائيات مالية - آخر 12 شهر
        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->subYear();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now();
        
        // إجمالي التحصيل من عقارات المالك
        $totalCollection = CollectionPayment::whereHas('property', function($q) use ($owner) {
            $q->where('owner_id', $owner->id);
        })
        ->where('collection_status', 'collected')
        ->whereBetween('paid_date', [$dateFrom, $dateTo])
        ->sum('total_amount');
        
        // حساب الرسوم الإدارية من متوسط نسب العقود
        $avgCommissionRate = \App\Models\PropertyContract::query()
            ->when($this->property_id, function($q) {
                $q->where('property_id', $this->property_id);
            })
            ->avg('commission_rate') ?? 5.00;
        
        $managementFees = $totalCollection * ($avgCommissionRate / 100);
        
        // صافي المبلغ المستحق للمالك
        $ownerDue = $totalCollection - $managementFees;
        
        // المبالغ المحولة للمالك فعلياً
        $paidToOwner = SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'collected')
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->sum('net_amount');
        
        // الرصيد المعلق
        $pendingBalance = $ownerDue - $paidToOwner;
        
        // عدد العمليات المالية
        $totalOperations = SupplyPayment::where('owner_id', $owner->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
        $completedOperations = SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'collected')
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->count();
        
        // آخر عملية تحويل
        $lastPayment = SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'collected')
            ->latest('paid_date')
            ->first();
        
        // العملية القادمة (المعلقة)
        $nextPayment = SupplyPayment::where('owner_id', $owner->id)
            ->where('supply_status', 'pending')
            ->oldest('created_at')
            ->first();
        
        // متوسط الدخل الشهري
        $averageMonthlyIncome = $paidToOwner / 12;
        
        // نسبة التحويل
        $transferRate = $ownerDue > 0 ? round(($paidToOwner / $ownerDue) * 100) : 0;

        return [
            // معلومات المالك
            'owner_name' => $owner->name,
            'owner_phone' => $owner->phone,
            'owner_secondary_phone' => $owner->secondary_phone,
            'owner_email' => $owner->email,
            'identity_file' => $owner->identity_file,
            'created_at' => $owner->created_at,
            
            // إحصائيات العقارات
            'properties_count' => $propertiesCount,
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $vacantUnits,
            'occupancy_rate' => $occupancyRate,
            'properties_list' => $owner->properties->pluck('name')->toArray(),
            
            // الإحصائيات المالية
            'total_collection' => $totalCollection,
            'management_fees' => $managementFees,
            'owner_due' => $ownerDue,
            'paid_to_owner' => $paidToOwner,
            'pending_balance' => $pendingBalance,
            'transfer_rate' => $transferRate,
            'average_monthly_income' => $averageMonthlyIncome,
            
            // معلومات العمليات
            'total_operations' => $totalOperations,
            'completed_operations' => $completedOperations,
            'completion_rate' => $totalOperations > 0 ? round(($completedOperations / $totalOperations) * 100) : 0,
            
            // آخر دفعة والدفعة القادمة
            'last_payment' => $lastPayment ? [
                'payment_number' => $lastPayment->payment_number,
                'amount' => $lastPayment->net_amount,
                'payment_date' => $lastPayment->paid_date,
            ] : null,
            
            'next_payment' => $nextPayment ? [
                'payment_number' => $nextPayment->payment_number,
                'amount' => $nextPayment->net_amount,
                'created_date' => $nextPayment->created_at,
            ] : null,
            
            // حالة المالك
            'is_active' => $propertiesCount > 0 && $occupancyRate > 0,
            'performance_level' => $transferRate >= 80 ? 'excellent' : ($transferRate >= 50 ? 'good' : 'needs_attention'),
        ];
    }

    private function getEmptyStatistics(): array
    {
        return [
            'owner_name' => 'غير محدد',
            'owner_phone' => null,
            'owner_secondary_phone' => null,
            'owner_email' => null,
            'identity_file' => null,
            'created_at' => null,
            'properties_count' => 0,
            'total_units' => 0,
            'occupied_units' => 0,
            'vacant_units' => 0,
            'occupancy_rate' => 0,
            'properties_list' => [],
            'total_collection' => 0,
            'management_fees' => 0,
            'owner_due' => 0,
            'paid_to_owner' => 0,
            'pending_balance' => 0,
            'transfer_rate' => 0,
            'average_monthly_income' => 0,
            'total_operations' => 0,
            'completed_operations' => 0,
            'completion_rate' => 0,
            'last_payment' => null,
            'next_payment' => null,
            'is_active' => false,
            'performance_level' => 'needs_attention',
        ];
    }

    private function getOwnerPaymentStatistics(SupplyPayment $payment): array
    {
        // تحميل العلاقات
        $payment->load(['owner', 'owner.properties']);
        
        // إجمالي المدفوعات للمالك
        $totalOwnerPayments = SupplyPayment::where('owner_id', $payment->owner_id)
            ->where('supply_status', 'collected')
            ->sum('net_amount');
        
        // المدفوعات المعلقة للمالك
        $pendingPayments = SupplyPayment::where('owner_id', $payment->owner_id)
            ->where('supply_status', 'pending')
            ->sum('net_amount');
        
        // عدد العمليات للمالك
        $totalOperations = SupplyPayment::where('owner_id', $payment->owner_id)->count();
        $completedOperations = SupplyPayment::where('owner_id', $payment->owner_id)
            ->where('supply_status', 'collected')
            ->count();
        
        // آخر عملية دفع للمالك
        $lastPaymentDate = SupplyPayment::where('owner_id', $payment->owner_id)
            ->where('supply_status', 'collected')
            ->where('id', '!=', $payment->id)
            ->latest('paid_date')
            ->value('paid_date');
        
        // معلومات العقارات
        $ownerProperties = $payment->owner ? $payment->owner->properties : collect();
        $propertiesCount = $ownerProperties->count();
        $totalUnits = $ownerProperties->sum('total_units') ?? 0;
        
        return [
            // معلومات العملية
            'payment_number' => $payment->payment_number,
            'amount' => $payment->gross_amount,
            'net_amount' => $payment->net_amount,
            'deductions' => $payment->gross_amount - $payment->net_amount,
            'payment_status' => $payment->supply_status,
            'payment_method' => 'bank_transfer',
            'payment_date' => $payment->paid_date,
            'bank_reference' => $payment->bank_transfer_reference,
            'notes' => $payment->notes,
            
            // معلومات المالك
            'owner_name' => $payment->owner?->name,
            'owner_phone' => $payment->owner?->phone,
            'owner_email' => $payment->owner?->email,
            'total_owner_payments' => $totalOwnerPayments,
            'pending_payments' => $pendingPayments,
            'total_operations' => $totalOperations,
            'completed_operations' => $completedOperations,
            'last_payment_date' => $lastPaymentDate,
            
            // معلومات العقارات
            'properties_count' => $propertiesCount,
            'total_units' => $totalUnits,
            'properties_names' => $ownerProperties->pluck('name')->join(', '),
            
            // الإحصائيات
            'completion_rate' => $totalOperations > 0 ? round(($completedOperations / $totalOperations) * 100) : 0,
            'average_payment' => $completedOperations > 0 ? round($totalOwnerPayments / $completedOperations, 2) : 0,
        ];
    }
}
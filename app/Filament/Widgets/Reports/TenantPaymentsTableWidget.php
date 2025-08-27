<?php

namespace App\Filament\Widgets\Reports;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\CollectionPayment;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;

class TenantPaymentsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'جدول المستأجرين';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public ?int $property_id = null;
    public ?int $unit_id = null;
    public ?int $tenant_id = null;
    public string $tenant_status = 'all';
    public string $report_type = 'summary';
    public ?string $date_from = null;
    public ?string $date_to = null;

    #[On('tenant-filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->property_id = $filters['property_id'] ?? null;
        $this->unit_id = $filters['unit_id'] ?? null;
        $this->tenant_id = $filters['tenant_id'] ?? null;
        $this->tenant_status = $filters['tenant_status'] ?? 'all';
        $this->report_type = $filters['report_type'] ?? 'summary';
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->searchable()
            ->searchPlaceholder('ابحث في المدفوعات...')
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الدفعة'),

                TextColumn::make('tenant.name')
                    ->label('المستأجر')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->placeholder('غير محدد'),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('month_year')
                    ->label('الشهر/السنة')
                    ->searchable()
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y/m') : 'غير محدد'),

                TextColumn::make('total_amount')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->searchable()
                    ->alignEnd()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

                TextColumn::make('due_date_start')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y/m/d')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),

                BadgeColumn::make('collection_status')
                    ->label('حالة التحصيل')
                    ->searchable(query: function ($query, string $search) {
                        // البحث بالعربي والإنجليزي
                        $statusMap = [
                            'تم التحصيل' => 'collected',
                            'محصل' => 'collected',
                            'مستحق' => 'due',
                            'متأخر' => 'overdue',
                            'مؤجل' => 'postponed',
                        ];
                        
                        // البحث في القيم العربية
                        $englishStatus = null;
                        foreach ($statusMap as $arabic => $english) {
                            if (str_contains($arabic, $search)) {
                                $englishStatus = $english;
                                break;
                            }
                        }
                        
                        if ($englishStatus) {
                            return $query->where('collection_status', $englishStatus);
                        }
                        
                        // البحث المباشر في القيم الإنجليزية
                        return $query->where('collection_status', 'like', "%{$search}%");
                    })
                    ->colors([
                        'success' => 'collected',
                        'warning' => 'due',
                        'danger' => 'overdue',
                        'secondary' => 'postponed',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'collected' => 'تم التحصيل',
                            'due' => 'مستحق',
                            'overdue' => 'متأخر',
                            'postponed' => 'مؤجل',
                            default => 'غير محدد'
                        };
                    })
                    ->alignCenter(),

                TextColumn::make('paid_date')
                    ->label('تاريخ الدفع')
                    ->date('Y/m/d')
                    ->searchable()
                    ->alignCenter()
                    ->placeholder('لم يدفع')
                    ->sortable()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('late_fee')
                    ->label('غرامة التأخير')
                    ->money('SAR')
                    ->searchable()
                    ->alignEnd()
                    ->default(0)
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delay_duration')
                    ->label('أيام التأخير')
                    ->placeholder('-')
                    ->alignCenter()
                    ->suffix(' يوم')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('paymentMethod.name')
                    ->label('طريقة الدفع')
                    ->placeholder('غير محدد')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                Action::make('view_report')
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn ($record) => $record->tenant_id)
                    ->modalHeading(fn ($record) => 'تقرير المستأجر: ' . ($record->tenant->name ?? 'غير محدد'))
                    ->modalContent(fn ($record) => view('filament.reports.tenant-comprehensive-report', [
                        'tenant' => $record->tenant,
                        'stats' => \App\Filament\Resources\TenantResource::getTenantStatistics($record->tenant),
                        'recentPayments' => \App\Filament\Resources\TenantResource::getRecentPayments($record->tenant),
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
            ->defaultSort('due_date_start', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->paginationPageOptions([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->poll('60s')
            ->deferLoading()
            ->emptyStateHeading('لا توجد مدفوعات')
            ->emptyStateDescription('لا توجد مدفوعات تطابق معايير البحث المحددة.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation|null
    {
        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfYear();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfYear();

        $query = CollectionPayment::query()
            ->with(['paymentStatus', 'paymentMethod', 'unit', 'property', 'tenant'])
            ->whereBetween('due_date_start', [$dateFrom, $dateTo]);

        // Apply property filter
        if ($this->property_id) {
            $query->where('property_id', $this->property_id);
        }

        // Apply unit filter
        if ($this->unit_id) {
            $query->where('unit_id', $this->unit_id);
        }

        // Apply tenant filter if specific tenant selected
        if ($this->tenant_id) {
            $query->where('tenant_id', $this->tenant_id);
        }

        // Apply tenant status filter
        switch ($this->tenant_status) {
            case 'active':
                $query->whereHas('unitContract', function($q) {
                    $q->where('contract_status', 'active');
                });
                break;
            case 'expired':
                $query->whereHas('unitContract', function($q) {
                    $q->where('contract_status', 'expired');
                });
                break;
            case 'defaulter':
                $query->where('collection_status', 'overdue');
                break;
        }

        // Order by due date
        $query->orderBy('due_date_start', 'desc');

        return $query;
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function getTableHeading(): ?string
    {
        $heading = 'سجل مدفوعات المستأجرين';
        
        if ($this->date_from || $this->date_to) {
            $dateFrom = $this->date_from ? Carbon::parse($this->date_from)->format('Y/m/d') : 'البداية';
            $dateTo = $this->date_to ? Carbon::parse($this->date_to)->format('Y/m/d') : 'النهاية';
            $heading .= " - من {$dateFrom} إلى {$dateTo}";
        }
        
        return $heading;
    }

    private function getPaymentStatistics(CollectionPayment $payment): array
    {
        // تحميل العلاقات
        $payment->load(['tenant', 'unit', 'property', 'unitContract', 'paymentStatus', 'paymentMethod']);
        
        // معلومات العقد
        $contract = $payment->unitContract;
        
        // إجمالي المدفوعات للمستأجر
        $totalTenantPayments = CollectionPayment::where('tenant_id', $payment->tenant_id)
            ->where('collection_status', 'collected')
            ->sum('total_amount');
        
        // المستحقات المتبقية للمستأجر
        $outstandingPayments = CollectionPayment::where('tenant_id', $payment->tenant_id)
            ->whereIn('collection_status', ['due', 'overdue'])
            ->sum('total_amount');
        
        // عدد المدفوعات المتأخرة للمستأجر
        $overdueCount = CollectionPayment::where('tenant_id', $payment->tenant_id)
            ->where('collection_status', 'overdue')
            ->count();
        
        // تاريخ آخر دفعة للمستأجر
        $lastPaymentDate = CollectionPayment::where('tenant_id', $payment->tenant_id)
            ->where('collection_status', 'collected')
            ->where('id', '!=', $payment->id)
            ->latest('paid_date')
            ->value('paid_date');
        
        // حساب أيام التأخير
        $daysLate = 0;
        if ($payment->collection_status == 'overdue' && $payment->due_date_end) {
            $daysLate = Carbon::parse($payment->due_date_end)->diffInDays(now());
        }
        
        // معلومات العقار والوحدة
        $unitInfo = $payment->unit ? [
            'name' => $payment->unit->name,
            'floor' => $payment->unit->floor_number,
            'rooms' => $payment->unit->rooms_count,
            'area' => $payment->unit->area_sqm,
            'rent' => $payment->unit->rent_price,
        ] : null;
        
        return [
            // معلومات الدفعة
            'payment_number' => $payment->payment_number,
            'receipt_number' => $payment->receipt_number,
            'payment_reference' => $payment->payment_reference,
            'month_year' => $payment->month_year,
            'amount' => $payment->amount,
            'late_fee' => $payment->late_fee,
            'total_amount' => $payment->total_amount,
            'collection_status' => $payment->collection_status,
            'payment_method' => $payment->paymentMethod?->name,
            'due_date_start' => $payment->due_date_start,
            'due_date_end' => $payment->due_date_end,
            'paid_date' => $payment->paid_date,
            'collection_date' => $payment->collection_date,
            'days_late' => $daysLate,
            'delay_reason' => $payment->delay_reason,
            'late_payment_notes' => $payment->late_payment_notes,
            
            // معلومات المستأجر
            'tenant_name' => $payment->tenant?->name,
            'tenant_phone' => $payment->tenant?->phone,
            'tenant_email' => $payment->tenant?->email,
            'total_tenant_payments' => $totalTenantPayments,
            'outstanding_payments' => $outstandingPayments,
            'overdue_count' => $overdueCount,
            'last_payment_date' => $lastPaymentDate,
            
            // معلومات العقار والوحدة
            'property_name' => $payment->property?->name,
            'unit_info' => $unitInfo,
            
            // معلومات العقد
            'contract_number' => $contract?->contract_number,
            'contract_start' => $contract?->start_date,
            'contract_end' => $contract?->end_date,
            'monthly_rent' => $contract?->monthly_rent,
            'security_deposit' => $contract?->security_deposit,
        ];
    }
}
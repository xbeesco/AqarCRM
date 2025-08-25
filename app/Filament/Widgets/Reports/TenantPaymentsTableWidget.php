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

class TenantPaymentsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'سجل المدفوعات';
    protected static ?int $sort = 2;

    public ?int $tenant_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;
    public string $report_type = 'summary';

    #[On('tenant-filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->tenant_id = $filters['tenant_id'] ?? null;
        $this->date_from = $filters['date_from'] ?? null;
        $this->date_to = $filters['date_to'] ?? null;
        $this->report_type = $filters['report_type'] ?? 'summary';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('month_year')
                    ->label('الشهر/السنة')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('m/Y') : 'غير محدد'),

                TextColumn::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->money('SAR')
                    ->weight(FontWeight::SemiBold)
                    ->color('success'),

                TextColumn::make('late_fee')
                    ->label('رسوم التأخير')
                    ->money('SAR')
                    ->default(0)
                    ->color('warning'),

                TextColumn::make('due_date_start')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y/m/d')
                    ->sortable(),

                TextColumn::make('paid_date')
                    ->label('تاريخ الدفع')
                    ->date('Y/m/d')
                    ->placeholder('لم يدفع')
                    ->sortable(),

                BadgeColumn::make('collection_status')
                    ->label('حالة التحصيل')
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
                    }),

                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->placeholder('غير محدد')
                    ->searchable(),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->placeholder('غير محدد')
                    ->searchable(),

                TextColumn::make('delay_duration')
                    ->label('مدة التأخير (يوم)')
                    ->placeholder('لا يوجد')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                TextColumn::make('paymentMethod.name')
                    ->label('طريقة الدفع')
                    ->placeholder('غير محدد'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collection_status')
                    ->label('حالة التحصيل')
                    ->options([
                        'collected' => 'تم التحصيل',
                        'due' => 'مستحق',
                        'overdue' => 'متأخر',
                        'postponed' => 'مؤجل',
                    ]),
                
                Tables\Filters\Filter::make('has_late_fee')
                    ->label('يحتوي على رسوم تأخير')
                    ->query(fn ($query) => $query->where('late_fee', '>', 0)),
                
                Tables\Filters\Filter::make('unpaid')
                    ->label('غير مدفوع')
                    ->query(fn ($query) => $query->whereNull('paid_date')),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض')
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('due_date_start', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('60s')
            ->emptyStateHeading('لا توجد مدفوعات')
            ->emptyStateDescription('يرجى اختيار مستأجر من الفلاتر أعلاه لعرض المدفوعات.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    protected function getTableQuery()
    {
        if (!$this->tenant_id) {
            return CollectionPayment::query()->whereRaw('0 = 1'); // Return empty query
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from) : now()->startOfMonth();
        $dateTo = $this->date_to ? Carbon::parse($this->date_to) : now()->endOfMonth();

        $query = CollectionPayment::where('tenant_id', $this->tenant_id)
            ->with(['paymentStatus', 'paymentMethod', 'unit', 'property'])
            ->whereBetween('due_date_start', [$dateFrom, $dateTo]);

        // Filter based on report type
        if ($this->report_type === 'payment_history') {
            // Show all payments for detailed payment history
            $query->orderBy('due_date_start', 'desc');
        } else {
            // For summary, limit to recent payments
            $query->limit(20)->orderBy('due_date_start', 'desc');
        }

        return $query;
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function getTableHeading(): ?string
    {
        if (!$this->tenant_id) {
            return 'سجل المدفوعات - يرجى اختيار مستأجر';
        }

        $dateFrom = $this->date_from ? Carbon::parse($this->date_from)->format('Y/m/d') : '';
        $dateTo = $this->date_to ? Carbon::parse($this->date_to)->format('Y/m/d') : '';
        
        return "سجل المدفوعات - من {$dateFrom} إلى {$dateTo}";
    }
}
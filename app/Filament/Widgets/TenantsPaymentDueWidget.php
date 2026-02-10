<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Exports\CollectionPaymentsExport;
use App\Models\CollectionPayment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Maatwebsite\Excel\Facades\Excel;

class TenantsPaymentDueWidget extends BaseWidget
{
    protected static ?string $heading = 'الدفعات المستحقة';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    public ?int $allowedDelayDays = null;

    public function mount(): void
    {
        // تحميل القيمة الافتراضية من الإعدادات (فقط أيام التأخير المسموح بها)
        $this->allowedDelayDays = (int) \App\Models\Setting::get('allowed_delay_days', 5);
    }

    public function table(Table $table): Table
    {
        // حساب التاريخ بناءً على أيام التأخير المسموح بها
        $thresholdDate = Carbon::now()->startOfDay()->subDays($this->allowedDelayDays ?? 0);

        return $table
            ->query(
                CollectionPayment::query()
                    ->with(['tenant', 'property', 'unit'])
                    ->where('due_date_start', '<=', $thresholdDate)
                    ->whereNull('collection_date')
                    ->where(function ($q) {
                        $q->whereNull('delay_duration')
                            ->orWhere('delay_duration', 0);
                    })
                    ->orderBy('property_id')
                    ->orderBy('due_date_start', 'asc')
            )
            ->headerActions([
                Action::make('export')
                    ->label('تصدير')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Table $table) {
                        $filename = 'المستحقات-' . date('Y-m-d') . '.xlsx';
                        $query = $table->getQuery()->clone();
                        return Excel::download(new CollectionPaymentsExport($query), $filename);
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('property.name')
                    ->label('العقار'),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('المستأجر'),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('الوحدة'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('القيمة')
                    ->money('SAR'),

                Tables\Columns\TextColumn::make('due_date_start')
                    ->label('التاريخ')

                    ->date('Y-m-d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.phone')
                    ->label('الهاتف'),

                Tables\Columns\TextColumn::make('payment_status_label')
                    ->label('الحالة')
                    ->badge()
                    ->color(
                        fn ($record): string => $record->payment_status_enum === PaymentStatus::OVERDUE ? 'danger' : 'gray'
                    ),
            ])
            ->recordActions([
                Action::make('postpone')
                    ->label('تأجيل')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn (?CollectionPayment $record): bool => $record?->can_be_postponed ?? false)
                    ->modalHeading('تأجيل الدفعة')
                    ->modalDescription('قم بتحديد مدة التأجيل وسبب التأجيل')
                    ->modalSubmitActionLabel('تأجيل')
                    ->modalCancelActionLabel('إلغاء')
                    ->form([
                        Forms\Components\TextInput::make('delay_duration')
                            ->label('مدة التأجيل (بالأيام)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(365),
                        Forms\Components\Textarea::make('delay_reason')
                            ->label('سبب التأجيل')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (CollectionPayment $record, array $data): void {
                        $record->postpone($data['delay_duration'], $data['delay_reason']);

                        Notification::make()
                            ->title('تم تأجيل الدفعة')
                            ->success()
                            ->send();
                    }),
                Action::make('confirm_receipt')
                    ->label('تأكيد الاستلام')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (?CollectionPayment $record): bool => $record?->can_be_collected ?? false)
                    ->modalHeading('تأكيد استلام الدفعة')
                    ->modalDescription(
                        fn (?CollectionPayment $record): string => $record ? 'أقر أنا '.auth()->user()->name.' باستلام مبلغ وقدره '.
                        number_format((float) $record->amount, 2).' ريال' : ''
                    )
                    ->modalSubmitActionLabel('تأكيد')
                    ->modalCancelActionLabel('إلغاء')
                    ->requiresConfirmation()
                    ->action(function (CollectionPayment $record): void {
                        $record->markAsCollected();

                        Notification::make()
                            ->title('تم تأكيد الاستلام')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('property_id', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('حالة الدفعة')
                    ->multiple()
                    ->options(PaymentStatus::options())
                    ->query(function ($query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        // استخدام scope الموديل الجديد byStatuses
                        return $query->byStatuses($data['values']);
                    }),

                Filter::make('allowed_delay_days')
                    ->label('أيام التأخير المسموح بها')
                    ->form([
                        TextInput::make('days')
                            ->label('أيام التأخير المسموح بها')
                            ->numeric()
                            ->default(fn () => $this->allowedDelayDays)
                            ->helperText('القيمة الافتراضية: '.\App\Models\Setting::get('allowed_delay_days', 5).' يوم'),
                    ])
                    ->query(function ($query, array $data) {
                        // الفلتر يعيد بناء الـ query بالكامل إذا تم تغيير القيمة
                        $days = (int) ($data['days'] ?? $this->allowedDelayDays);
                        $thresholdDate = Carbon::now()->startOfDay()->subDays($days);

                        // إعادة تطبيق الشروط الأساسية مع القيمة الجديدة
                        return $query->where('due_date_start', '<=', $thresholdDate);
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $days = $data['days'] ?? $this->allowedDelayDays;

                        return 'تأخير أكثر من '.$days.' يوم';
                    }),

            ])
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->emptyStateHeading('لا توجد دفعات مستحقة')
            ->emptyStateDescription('جميع المستحقات محصلة ')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function getTableHeading(): ?string
    {
        $thresholdDate = Carbon::now()->startOfDay()->subDays($this->allowedDelayDays ?? 0);

        $query = CollectionPayment::query()
            ->where('due_date_start', '<=', $thresholdDate)
            ->whereNull('collection_date')
            ->where(function ($q) {
                $q->whereNull('delay_duration')
                    ->orWhere('delay_duration', 0);
            });

        $totalDue = $query->count();
        $totalAmount = (float) $query->sum('amount');
        $formattedAmount = number_format($totalAmount, 2).' ريال';

        return static::$heading." ({$totalDue} دفعة - إجمالي: {$formattedAmount})";
    }
}

<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Models\CollectionPayment;
use App\Models\Setting;
use App\Exports\CollectionPaymentsExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Widgets\TableWidget as BaseWidget;
use Carbon\Carbon;

class TenantsPaymentDueWidget extends BaseWidget
{
    protected static ?string $heading = 'الدفعات المستحقة';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CollectionPayment::with(['tenant', 'property', 'unit'])
                    ->dueForCollection()
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
                        fn($record): string => $record->payment_status_enum === PaymentStatus::OVERDUE ? 'danger' : 'gray'
                    ),
            ])
            ->recordActions([
                Action::make('postpone')
                    ->label('تأجيل')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn(?CollectionPayment $record): bool => $record?->can_be_postponed ?? false)
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
                    ->visible(fn(?CollectionPayment $record): bool => $record?->can_be_collected ?? false)
                    ->modalHeading('تأكيد استلام الدفعة')
                    ->modalDescription(
                        fn(?CollectionPayment $record): string => $record ? 'أقر أنا ' . auth()->user()->name . ' باستلام مبلغ وقدره ' .
                        number_format((float) $record->amount, 2) . ' ريال' : ''
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
                            ->default(fn() => (new CollectionPayment())->getTotalGraceThreshold())
                            ->helperText('القيمة الافتراضية من إعدادات النظام'),
                    ])
                    ->query(function ($query, array $data) {
                        if (blank($data['days'])) {
                            return $query;
                        }

                        $days = (int) $data['days'];
                        $thresholdDate = Carbon::now()->startOfDay()->subDays($days);

                        return $query->where('due_date_start', '<=', $thresholdDate);
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (blank($data['days'])) {
                            return null;
                        }

                        return 'تأخير أكثر من ' . $data['days'] . ' أيام';
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
        $totalDue = CollectionPayment::dueForCollection()->count();

        $totalAmount = (float) CollectionPayment::dueForCollection()->sum('amount');

        $formattedAmount = number_format($totalAmount, 2) . ' ريال';

        return static::$heading . " ({$totalDue} دفعة - إجمالي: {$formattedAmount})";
    }
}

<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\PaymentStatus;
use App\Models\CollectionPayment;
use App\Services\CollectionPaymentService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

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
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('property.name')
                    ->label('العقار'),

                TextColumn::make('tenant.name')
                    ->label('المستأجر'),

                TextColumn::make('unit.name')
                    ->label('الوحدة'),

                TextColumn::make('amount')
                    ->label('القيمة')
                    ->money('SAR'),

                TextColumn::make('due_date_start')
                    ->label('التاريخ')

                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('tenant.phone')
                    ->label('الهاتف'),

                TextColumn::make('payment_status_label')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($record): string => $record->payment_status_enum === PaymentStatus::OVERDUE ? 'danger' : 'gray'
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
                    ->schema([
                        TextInput::make('delay_duration')
                            ->label('مدة التأجيل (بالأيام)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(365),
                        Textarea::make('delay_reason')
                            ->label('سبب التأجيل')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (CollectionPayment $record, array $data): void {
                        app(CollectionPaymentService::class)->postponePayment(
                            $record,
                            $data['delay_duration'],
                            $data['delay_reason']
                        );

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
                            number_format($record->amount, 2).' ريال' : ''
                    )
                    ->modalSubmitActionLabel('تأكيد')
                    ->modalCancelActionLabel('إلغاء')
                    ->requiresConfirmation()
                    ->action(function (CollectionPayment $record): void {
                        app(CollectionPaymentService::class)->markAsCollected($record, auth()->id());

                        Notification::make()
                            ->title('تم تأكيد الاستلام')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('property_id', 'asc')
            ->filters([
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_status')
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

        $totalAmount = CollectionPayment::dueForCollection()->sum('amount');

        $formattedAmount = number_format($totalAmount, 2).' ريال';

        return static::$heading." ({$totalDue} دفعة - إجمالي: {$formattedAmount})";
    }
}

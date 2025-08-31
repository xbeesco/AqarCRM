<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\CollectionPayment;
use Carbon\Carbon;
use App\Helpers\DateHelper;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class TenantsPaymentDueWidget extends BaseWidget
{
    protected static ?string $heading = 'الدفعات المستحقة';
    
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;
    
    protected function getToday(): Carbon
    {
        return DateHelper::getCurrentDate()->copy()->startOfDay();
    }
    
    public function table(Table $table): Table
    {
        $today = $this->getToday();
        
        return $table
            ->query(
                CollectionPayment::with(['tenant', 'property', 'unit'])
                    ->dueForCollection()
                    ->orderBy('property_id')
                    ->orderBy('due_date_start', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable(),

                    Tables\Columns\TextColumn::make('tenant.name')
                    ->label('المستأجر')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('قيمة الدفعة')
                    ->money('SAR'),
                    
                Tables\Columns\TextColumn::make('due_date_start')
                    ->label('تاريخ الدفعة')
                    ->date('Y-m-d')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tenant.phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->default('-'),
            ])
            ->recordActions([
                Action::make('postpone')
                    ->label('تأجيل')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->visible(fn (?CollectionPayment $record): bool => 
                        $record && $record->collection_date === null && 
                        ($record->delay_duration === null || $record->delay_duration == 0)
                    )
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
                        $record->update([
                            'delay_duration' => $data['delay_duration'],
                            'delay_reason' => $data['delay_reason'],
                            'collection_status' => 'postponed',
                        ]);
                        
                        Notification::make()
                            ->title('تم تأجيل الدفعة')
                            ->success()
                            ->send();
                    }),
                Action::make('confirm_receipt')
                    ->label('تأكيد الاستلام')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (?CollectionPayment $record): bool => 
                        $record && $record->collection_date === null
                    )
                    ->modalHeading('تأكيد استلام الدفعة')
                    ->modalDescription(fn (?CollectionPayment $record): string => 
                        $record ? "أقر أنا " . auth()->user()->name . " باستلام مبلغ وقدره " . 
                        number_format($record->amount, 2) . " ريال" : ""
                    )
                    ->modalSubmitActionLabel('تأكيد')
                    ->modalCancelActionLabel('إلغاء')
                    ->requiresConfirmation()
                    ->action(function (CollectionPayment $record): void {
                        $record->update([
                            'collection_date' => DateHelper::getCurrentDate(),
                            'paid_date' => DateHelper::getCurrentDate(),
                            'collection_status' => 'collected',
                        ]);
                        
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
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('متأخر')
                    ->query(fn ($query) => $query->overduePayments()),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('لا توجد دفعات مستحقة')
            ->emptyStateDescription('جميع المستحقات محصلة ')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
    
    protected function getTableHeading(): ?string
    {
        $today = $this->getToday();
        
        $totalDue = CollectionPayment::dueForCollection()->count();
            
        $totalAmount = CollectionPayment::dueForCollection()->sum('amount');
        
        $formattedAmount = number_format($totalAmount, 2) . ' ريال';
        
        return static::$heading . " ({$totalDue} دفعة - إجمالي: {$formattedAmount})";
    }
}
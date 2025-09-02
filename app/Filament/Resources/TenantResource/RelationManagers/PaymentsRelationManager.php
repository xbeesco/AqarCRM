<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\CollectionPayment;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    
    protected static ?string $title = 'الدفعات';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('month_year')
                    ->label('الشهر')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('المبلغ الإجمالي')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('due_date_start')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),
                BadgeColumn::make('payment_status_label')
                    ->label('حالة الدفعة')
                    ->state(function (CollectionPayment $record) {
                        return $record->payment_status_label;
                    })
                    ->color(function (CollectionPayment $record) {
                        return match($record->payment_status->value) {
                            'collected' => 'success',
                            'due' => 'warning', 
                            'overdue' => 'danger',
                            'postponed' => 'secondary',
                            'upcoming' => 'primary',
                            default => 'gray'
                        };
                    }),
                TextColumn::make('collection_date')
                    ->label('تاريخ التحصيل')
                    ->date('Y-m-d')
                    ->toggleable()
                    ->sortable()
                    ->placeholder('لم يحصل'),
                TextColumn::make('delay_duration')
                    ->label('أيام التأجيل')
                    ->toggleable()
                    ->placeholder('-'),
                TextColumn::make('late_fee')
                    ->label('غرامة التأخير')
                    ->money('SAR')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('collected')
                    ->label('المحصلة')
                    ->query(fn ($query) => $query->collectedPayments()),
                Tables\Filters\Filter::make('due')
                    ->label('المستحقة')
                    ->query(fn ($query) => $query->dueForCollection()),
                Tables\Filters\Filter::make('overdue')
                    ->label('المتأخرة')
                    ->query(fn ($query) => $query->overduePayments()),
                Tables\Filters\Filter::make('postponed')
                    ->label('المؤجلة')
                    ->query(fn ($query) => $query->postponedPayments()),
                Tables\Filters\Filter::make('current_month')
                    ->label('الشهر الحالي')
                    ->query(fn ($query) => $query->whereBetween('due_date_start', [now()->startOfMonth(), now()->endOfMonth()])),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->deferFilters()
            ->headerActions([
                // يمكن إضافة أزرار إنشاء دفعة جديدة هنا إذا أردنا
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date_start', 'desc');
    }
}
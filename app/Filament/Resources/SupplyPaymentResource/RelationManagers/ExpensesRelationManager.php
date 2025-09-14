<?php

namespace App\Filament\Resources\SupplyPaymentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';
    protected static ?string $title = 'المصروفات والنفقات';
    protected static ?string $modelLabel = 'مصروف';
    protected static ?string $pluralModelLabel = 'المصروفات';
    
    public function table(Table $table): Table
    {
        // الحصول على دفعة التوريد الحالية
        $supplyPayment = $this->ownerRecord;
        $expenses = $supplyPayment->getExpensesDetails();
        
        return $table
            ->query(fn () => \App\Models\Expense::query()
                ->whereIn('id', $expenses->pluck('id'))
            )
            ->columns([
                TextColumn::make('date')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('النوع')
                    ->getStateUsing(fn (\App\Models\Expense $record): string => $record->type_name)
                    ->badge()
                    ->color(fn (\App\Models\Expense $record): string => $record->type_color),
                TextColumn::make('desc')
                    ->label('الوصف')
                    ->wrap()
                    ->limit(50),
                TextColumn::make('subject')
                    ->label('مرتبطة بـ')
                    ->state(function ($record) {
                        $type = class_basename($record->subject_type);
                        if ($type === 'Property') {
                            return $record->subject?->name ?? 'العقار';
                        } elseif ($type === 'Unit') {
                            // جلب الوحدة مع معلوماتها
                            $unit = \App\Models\Unit::find($record->subject_id);
                            if ($unit) {
                                return $unit->name;
                            }
                            return 'وحدة';
                        }
                        return '-';
                    })
                    ->badge()
                    ->color(fn ($record) => class_basename($record->subject_type) === 'Property' ? 'primary' : 'warning'),
                TextColumn::make('cost')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->color('danger')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()
                        ->label('إجمالي المصروفات')
                        ->money('SAR')),
            ])
            ->defaultSort('date', 'desc')
            ->paginated(false)
            ->emptyStateHeading('لا توجد مصروفات')
            ->emptyStateDescription('لا توجد مصروفات مسجلة خلال هذه الفترة');
    }
}
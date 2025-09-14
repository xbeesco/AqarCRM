<?php

namespace App\Filament\Resources\SupplyPaymentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'collectionPayments';

    protected static ?string $title = 'دفعات التحصيل من المستأجرين';

    protected static ?string $modelLabel = 'دفعة تحصيل';

    protected static ?string $pluralModelLabel = 'دفعات التحصيل';

    public function table(Table $table): Table
    {
        // الحصول على دفعة التوريد الحالية
        $supplyPayment = $this->ownerRecord;
        $supplyPaymentService = app(\App\Services\SupplyPaymentService::class);
        $collectionPayments = $supplyPaymentService->getCollectionPaymentsDetails($supplyPayment);
        $commissionRate = $supplyPayment->commission_rate;

        return $table
            ->query(fn () => \App\Models\CollectionPayment::query()
                ->whereIn('id', $collectionPayments->pluck('id'))
            )
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->label('المستأجر')
                    ->searchable(),
                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->state(fn ($record) => $record->unit ? $record->unit->name : '-')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('المبلغ المحصل')
                    ->money('SAR')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()
                        ->label('الإجمالي')
                        ->money('SAR')),
                TextColumn::make('commission')
                    ->label('العمولة ('.$commissionRate.'%)')
                    ->state(fn ($record) => number_format($record->total_amount * ($commissionRate / 100), 2))
                    ->suffix(' ريال')
                    ->color('warning'),
                TextColumn::make('net_amount')
                    ->label('الصافي بعد العمولة')
                    ->state(fn ($record) => number_format($record->total_amount - ($record->total_amount * ($commissionRate / 100)), 2))
                    ->suffix(' ريال')
                    ->weight(FontWeight::Bold)
                    ->color('success'),
                TextColumn::make('paid_date')
                    ->label('تاريخ الدفع')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('month_year')
                    ->label('الشهر')
                    ->searchable(),
            ])
            ->defaultSort('paid_date', 'desc')
            ->paginated(false);
    }
}

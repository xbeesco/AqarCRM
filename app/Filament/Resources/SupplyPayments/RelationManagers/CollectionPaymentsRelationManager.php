<?php

namespace App\Filament\Resources\SupplyPayments\RelationManagers;

use App\Models\CollectionPayment;
use App\Services\PaymentAssignmentService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'collectionPayments';

    protected static ?string $title = 'دفعات التحصيل';

    protected static ?string $modelLabel = 'دفعة تحصيل';

    protected static ?string $pluralModelLabel = 'دفعات التحصيل';

    public function table(Table $table): Table
    {
        $supplyPayment = $this->ownerRecord;
        $paymentAssignmentService = app(PaymentAssignmentService::class);

        $invoiceDetails = $supplyPayment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $supplyPayment->month_year.'-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        $categorizedData = $paymentAssignmentService->getCategorizedPaymentsForPeriod(
            $supplyPayment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );

        $allCategorizedPayments = collect();
        foreach ($categorizedData['categories'] as $type => $category) {
            if ($category['payments']->count() > 0) {
                foreach ($category['payments'] as $payment) {
                    $payment->category_type = $type;
                    $payment->category_info = [
                        'type' => $type,
                        'name' => $category['name'],
                        'counted' => $category['counted'],
                    ];
                    $allCategorizedPayments->push($payment);
                }
            }
        }

        $commissionRate = $supplyPayment->commission_rate;

        return $table
            ->query(fn () => CollectionPayment::query()
                ->whereIn('id', $allCategorizedPayments->pluck('id'))
            )
            ->columns([
                TextColumn::make('payment_number')
                    ->label('رقم الدفعة')
                    ->copyable(),

                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->state(fn ($record) => $record->unit ? $record->unit->name : '-'),

                TextColumn::make('tenant.name')
                    ->label('المستأجر'),

                TextColumn::make('due_date_start')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d'),

                TextColumn::make('paid_date')
                    ->label('تاريخ الدفع')
                    ->date('Y-m-d'),

                TextColumn::make('payment_type')
                    ->label('الحالة')
                    ->state(function ($record) use ($paymentAssignmentService, $periodStart, $periodEnd) {
                        $typeInfo = $paymentAssignmentService->getPaymentTypeForPeriod($record, $periodStart, $periodEnd);

                        return $typeInfo['type'].': '.$typeInfo['name'];
                    })
                    ->badge()
                    ->color(function ($record) use ($paymentAssignmentService, $periodStart, $periodEnd) {
                        $typeInfo = $paymentAssignmentService->getPaymentTypeForPeriod($record, $periodStart, $periodEnd);

                        return $typeInfo['color'];
                    }),

                TextColumn::make('commission')
                    ->label('العمولة')
                    ->state(function ($record) use ($paymentAssignmentService, $periodStart, $periodEnd, $commissionRate) {
                        $typeInfo = $paymentAssignmentService->getPaymentTypeForPeriod($record, $periodStart, $periodEnd);
                        if ($typeInfo['counted']) {
                            return number_format($record->total_amount * ($commissionRate / 100), 2);
                        }

                        return '';
                    }),

                TextColumn::make('net_amount')
                    ->label('صافي للمالك')
                    ->state(function ($record) use ($paymentAssignmentService, $periodStart, $periodEnd, $commissionRate) {
                        $typeInfo = $paymentAssignmentService->getPaymentTypeForPeriod($record, $periodStart, $periodEnd);
                        if ($typeInfo['counted']) {
                            return number_format($record->total_amount - ($record->total_amount * ($commissionRate / 100)), 2);
                        }

                        return '';
                    }),
            ])
            ->filters([])
            ->defaultSort('due_date_start', 'asc')
            ->paginated(false)
            ->heading(null);
    }
}

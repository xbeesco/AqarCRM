<?php

namespace App\Filament\Resources\SupplyPaymentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class CollectionPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'collectionPayments';

    protected static ?string $title = 'دفعات التحصيل';

    protected static ?string $modelLabel = 'دفعة تحصيل';

    protected static ?string $pluralModelLabel = 'دفعات التحصيل';

    public function table(Table $table): Table
    {
        // الحصول على دفعة التوريد الحالية
        $supplyPayment = $this->ownerRecord;
        $supplyPaymentService = app(\App\Services\SupplyPaymentService::class);
        $paymentAssignmentService = app(\App\Services\PaymentAssignmentService::class);

        // الحصول على الدفعات المصنفة
        $invoiceDetails = $supplyPayment->invoice_details ?? [];
        $periodStart = $invoiceDetails['period_start'] ?? $supplyPayment->month_year . '-01';
        $periodEnd = $invoiceDetails['period_end'] ?? date('Y-m-t', strtotime($periodStart));

        $categorizedData = $paymentAssignmentService->getCategorizedPaymentsForPeriod(
            $supplyPayment->propertyContract->property_id,
            $periodStart,
            $periodEnd
        );

        // جمع كل الدفعات من جميع الأنواع
        $allCategorizedPayments = collect();
        foreach ($categorizedData['categories'] as $type => $category) {
            if ($category['payments']->count() > 0) {
                foreach ($category['payments'] as $payment) {
                    $payment->category_type = $type;
                    $payment->category_info = [
                        'type' => $type,
                        'name' => $category['name'],
                        'counted' => $category['counted']
                    ];
                    $allCategorizedPayments->push($payment);
                }
            }
        }

        $commissionRate = $supplyPayment->commission_rate;

        return $table
            ->query(fn () => \App\Models\CollectionPayment::query()
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
                        return $typeInfo['type']  .': ' . $typeInfo['name'];
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
            ->filters([
                // SelectFilter::make('category_type')
                //     ->label('نوع التصنيف')
                //     ->options([
                //         '1' => 'النوع 1: دفعات الفترة غير المدفوعة',
                //         '2' => 'النوع 2: دفعات الفترة المدفوعة خلالها',
                //         '3' => 'النوع 3: دفعات الفترة المدفوعة مسبقاً',
                //         '4' => 'النوع 4: دفعات الفترة المدفوعة متأخراً',
                //         '5' => 'النوع 5: متأخرات محصلة',
                //         '6' => 'النوع 6: دفعات مستقبلية محصلة مبكراً',
                //     ])
                //     ->query(function ($query, $state) use ($allCategorizedPayments) {
                //         if ($state['value']) {
                //             $filteredIds = $allCategorizedPayments
                //                 ->filter(fn($p) => $p->category_type == $state['value'])
                //                 ->pluck('id');
                //             return $query->whereIn('id', $filteredIds);
                //         }
                //         return $query;
                //     }),

                // SelectFilter::make('counted_status')
                //     ->label('حالة الاحتساب')
                //     ->options([
                //         'counted' => '✅ تُحتسب للمالك',
                //         'not_counted' => '❌ لا تُحتسب',
                //     ])
                //     ->query(function ($query, $state) use ($allCategorizedPayments) {
                //         if ($state['value'] === 'counted') {
                //             $countedTypes = [2, 3, 5];
                //             $filteredIds = $allCategorizedPayments
                //                 ->filter(fn($p) => in_array($p->category_type, $countedTypes))
                //                 ->pluck('id');
                //             return $query->whereIn('id', $filteredIds);
                //         } elseif ($state['value'] === 'not_counted') {
                //             $notCountedTypes = [1, 4, 6];
                //             $filteredIds = $allCategorizedPayments
                //                 ->filter(fn($p) => in_array($p->category_type, $notCountedTypes))
                //                 ->pluck('id');
                //             return $query->whereIn('id', $filteredIds);
                //         }
                //         return $query;
                //     })
            ])
            ->defaultSort('due_date_start', 'asc')
            ->paginated(false)
            ->heading(null)
            // ->description(function () use ($categorizedData) {
            //     $summary = "إجمالي المحتسب: " . number_format($categorizedData['counted_total'], 2) . " ريال";
            //     $summary .= " | إجمالي غير المحتسب: " . number_format($categorizedData['uncounted_total'], 2) . " ريال";
            //     return $summary;
            // })
            ;
    }
}

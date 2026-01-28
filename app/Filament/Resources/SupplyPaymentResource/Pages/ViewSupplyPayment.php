<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use App\Filament\Resources\SupplyPaymentResource;
use App\Services\SupplyPaymentService;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ViewSupplyPayment extends ViewRecord
{
    protected static string $resource = SupplyPaymentResource::class;

    protected ?SupplyPaymentService $supplyPaymentService = null;

    protected function resolveRecord($key): Model
    {
        $this->supplyPaymentService = app(SupplyPaymentService::class);

        return parent::resolveRecord($key);
    }

    protected function getSupplyPaymentService(): SupplyPaymentService
    {
        if ($this->supplyPaymentService === null) {
            $this->supplyPaymentService = app(SupplyPaymentService::class);
        }

        return $this->supplyPaymentService;
    }

    public function getRelationManagers(): array
    {
        // TODO: Re-implement collection payments and expenses display as custom infolist sections
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        $amounts = $this->getSupplyPaymentService()->calculateAmountsFromPeriod($this->record);

        return $schema
            ->components([
                Section::make('معلومات دفعة التوريد')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('propertyContract.property.name')
                                    ->label('العقار'),
                                TextEntry::make('period_start')
                                    ->label('من')
                                    ->state(fn () => "{$amounts['period_start']}"),
                                TextEntry::make('period_end')
                                    ->label('الي')
                                    ->state(fn () => "{$amounts['period_end']}"),

                                TextEntry::make('supply_status')
                                    ->label('حالة التوريد')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'worth_collecting' => 'info',
                                        'collected' => 'success',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pending' => 'قيد الانتظار',
                                        'worth_collecting' => 'تستحق التوريد',
                                        'collected' => 'تم التوريد',
                                        default => $state,
                                    }),
                                TextEntry::make('due_date')
                                    ->label('تاريخ الاستحقاق')
                                    ->date('Y-m-d'),
                                TextEntry::make('paid_date')
                                    ->label('تاريخ التوريد')
                                    ->date('Y-m-d')
                                    ->placeholder('لم يتم التوريد بعد'),

                            ]),
                    ]),

                Section::make('الحسابات المالية')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('gross_calculated')
                                    ->label('إجمالي المحصل')
                                    ->state(number_format($amounts['gross_amount'], 2).' ريال'),
                                TextEntry::make('commission_calculated')
                                    ->label('العمولة ('.$this->record->commission_rate.'%)')
                                    ->state(number_format($amounts['commission_amount'], 2).' ريال'),
                                TextEntry::make('expenses_calculated')
                                    ->label('المصروفات')
                                    ->state(number_format($amounts['maintenance_deduction'], 2).' ريال'),
                                TextEntry::make('net_calculated')
                                    ->label('صافي المستحق ')
                                    ->state(number_format($amounts['net_amount'], 2).' ريال'),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $hasPendingPayments = $this->getSupplyPaymentService()->hasPendingPreviousPayments($this->record);
        $pendingPayments = $hasPendingPayments ? $this->getSupplyPaymentService()->getPendingPreviousPayments($this->record) : collect();

        $amounts = $this->getSupplyPaymentService()->calculateAmountsFromPeriod($this->record);
        $netAmount = $amounts['net_amount'];

        $isSettlement = $netAmount <= 0;

        $actions = [];

        if ($hasPendingPayments && ! $this->record->paid_date) {
            $actions[] = Action::make('pending_payments_notice')
                ->label('يوجد دفعات سابقة غير مؤكدة')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->disabled()
                ->modalHeading('دفعات سابقة في الانتظار')
                ->modalDescription(function () use ($pendingPayments) {
                    $html = '<div style="text-align: right; direction: rtl;">';
                    $html .= '<p><strong>لا يمكن تأكيد هذه الدفعة حتى يتم توريد الدفعات التالية:</strong></p>';
                    $html .= '<ul style="list-style-type: disc; padding-right: 20px;">';

                    foreach ($pendingPayments as $payment) {
                        $paymentAmounts = $this->getSupplyPaymentService()->calculateAmountsFromPeriod($payment);
                        $html .= '<li style="margin-bottom: 10px;">';
                        $html .= '<strong>دفعة شهر:</strong> '.$payment->month_year.'<br>';
                        $html .= '<strong>تاريخ الاستحقاق:</strong> '.$payment->due_date->format('Y-m-d').'<br>';
                        $html .= '<strong>المبلغ المستحق:</strong> '.number_format($paymentAmounts['net_amount'], 2).' ريال';
                        $html .= '</li>';
                    }

                    $html .= '</ul>';
                    $html .= '<p style="margin-top: 15px; color: #d97706;"><strong>يجب توريد هذه الدفعات بالترتيب الزمني</strong></p>';
                    $html .= '</div>';

                    return new HtmlString($html);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق');
        }

        if (! $hasPendingPayments || $this->record->paid_date) {
            $actions[] = Action::make('confirm_payment')
                ->label($isSettlement ? 'تأكيد التسوية' : 'تأكيد التوريد')
                ->icon($isSettlement ? 'heroicon-o-document-check' : 'heroicon-o-check-circle')
                ->color($isSettlement ? 'warning' : 'success')
                ->requiresConfirmation()
                ->modalHeading($isSettlement ? 'تأكيد التسوية' : 'تأكيد توريد المبلغ')
                ->modalDescription(function () use ($netAmount, $isSettlement) {
                    $ownerName = $this->record->owner?->name ?? 'غير محدد';
                    $propertyName = $this->record->propertyContract?->property?->name ?? 'غير محدد';

                    if ($isSettlement) {
                        if ($netAmount < 0) {
                            // Negative value - Owner owes the company
                            return new HtmlString(
                                "<div style='text-align: right; direction: rtl;'>
                                    <p><strong>تأكيد التسوية:</strong></p>
                                    <p>المالك: <strong>{$ownerName}</strong></p>
                                    <p>العقار: <strong>{$propertyName}</strong></p>
                                    <p>المبلغ المستحق: <strong style='color: red;'>".number_format(abs($netAmount), 2).' ريال</strong> (دين على المالك)</p>
                                </div>'
                            );
                        } else {
                            // Zero value - No outstanding amounts
                            return new HtmlString(
                                "<div style='text-align: right; direction: rtl;'>
                                    <p><strong>تأكيد التسوية:</strong></p>
                                    <p>المالك: <strong>{$ownerName}</strong></p>
                                    <p>العقار: <strong>{$propertyName}</strong></p>
                                    <p>الحالة: <strong style='color: orange;'>لا توجد مستحقات</strong></p>
                                </div>"
                            );
                        }
                    } else {
                        // Positive value - Normal supply payment
                        $userName = auth()->user()->name;

                        return new HtmlString(
                            "<div style='text-align: right; direction: rtl;'>
                                <p>أقر أنا <strong>{$userName}</strong> بتوريد:</p>
                                <p>المبلغ: <strong style='color: green;'>".number_format($netAmount, 2)." ريال</strong></p>
                                <p>للمالك: <strong>{$ownerName}</strong></p>
                                <p>العقار: <strong>{$propertyName}</strong></p>
                            </div>"
                        );
                    }
                })
                ->modalSubmitActionLabel($isSettlement ? 'تأكيد التسوية' : 'تأكيد التوريد')
                ->modalIcon($isSettlement ? 'heroicon-o-document-check' : 'heroicon-o-check-circle')
                ->modalIconColor($isSettlement ? 'warning' : 'success')
                ->visible(fn () => $this->record &&
                    ! $this->record->paid_date &&
                    $this->record->due_date &&
                    now()->gte($this->record->due_date) &&
                    ! $hasPendingPayments
                )
                ->action(function () {
                    $result = $this->getSupplyPaymentService()->confirmSupplyPayment(
                        $this->record,
                        auth()->id()
                    );

                    if ($result['success']) {
                        Notification::make()
                            ->title($result['is_settlement'] ? 'تم تأكيد التسوية' : 'تم تأكيد التوريد')
                            ->body($result['message'])
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('index'));
                    } else {
                        Notification::make()
                            ->title('خطأ في التأكيد')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                });
        }

        return $actions;
    }
}

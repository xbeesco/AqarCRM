<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use App\Filament\Resources\SupplyPaymentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Enums\FontWeight;

class ViewSupplyPayment extends ViewRecord
{
    protected static string $resource = SupplyPaymentResource::class;
    
    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\SupplyPaymentResource\RelationManagers\CollectionPaymentsRelationManager::class,
            \App\Filament\Resources\SupplyPaymentResource\RelationManagers\ExpensesRelationManager::class,
        ];
    }
    
    public function infolist(Schema $schema): Schema
    {
        // حساب القيم
        $amounts = $this->record->calculateAmountsFromPeriod();
        
        return $schema
            ->schema([
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
                                    ->date('d/m/Y'),
                                TextEntry::make('paid_date')
                                    ->label('تاريخ التوريد')
                                    ->date('d/m/Y')
                                    ->placeholder('لم يتم التوريد بعد'),

                            ]),
                    ]),
                    
                Section::make('الحسابات المالية')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('gross_calculated')
                                    ->label('إجمالي المحصل')
                                    ->state(number_format($amounts['gross_amount'], 2) . ' ريال'),
                                TextEntry::make('commission_calculated')
                                    ->label('العمولة (' . $this->record->commission_rate . '%)')
                                    ->state(number_format($amounts['commission_amount'], 2) . ' ريال'),
                                TextEntry::make('expenses_calculated')
                                    ->label('المصروفات')
                                    ->state(number_format($amounts['maintenance_deduction'], 2) . ' ريال'),
                                TextEntry::make('net_calculated')
                                    ->label('صافي المستحق ')
                                    ->state(number_format($amounts['net_amount'], 2) . ' ريال'),
                            ]),
                    ]),
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        // حساب القيمة لتحديد نوع الزر المطلوب
        $amounts = $this->record->calculateAmountsFromPeriod();
        $netAmount = $amounts['net_amount'];

        // تحديد نوع العملية بناءً على القيمة
        $isSettlement = $netAmount <= 0; // تسوية إذا كانت القيمة صفر أو سالبة

        return [
            // زر تأكيد التوريد أو التسوية
            \Filament\Actions\Action::make('confirm_payment')
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
                            // قيمة سالبة - المالك مدين للشركة
                            return new \Illuminate\Support\HtmlString(
                                "<div style='text-align: right; direction: rtl;'>
                                    <p><strong>تأكيد التسوية:</strong></p>
                                    <p>المالك: <strong>{$ownerName}</strong></p>
                                    <p>العقار: <strong>{$propertyName}</strong></p>
                                    <p>المبلغ المستحق: <strong style='color: red;'>" . number_format(abs($netAmount), 2) . " ريال</strong> (دين على المالك)</p>
                                </div>"
                            );
                        } else {
                            // قيمة صفر - لا توجد مستحقات
                            return new \Illuminate\Support\HtmlString(
                                "<div style='text-align: right; direction: rtl;'>
                                    <p><strong>تأكيد التسوية:</strong></p>
                                    <p>المالك: <strong>{$ownerName}</strong></p>
                                    <p>العقار: <strong>{$propertyName}</strong></p>
                                    <p>الحالة: <strong style='color: orange;'>لا توجد مستحقات</strong></p>
                                </div>"
                            );
                        }
                    } else {
                        // قيمة موجبة - توريد عادي
                        $userName = auth()->user()->name;
                        return new \Illuminate\Support\HtmlString(
                            "<div style='text-align: right; direction: rtl;'>
                                <p>أقر أنا <strong>{$userName}</strong> بتوريد:</p>
                                <p>المبلغ: <strong style='color: green;'>" . number_format($netAmount, 2) . " ريال</strong></p>
                                <p>للمالك: <strong>{$ownerName}</strong></p>
                                <p>العقار: <strong>{$propertyName}</strong></p>
                            </div>"
                        );
                    }
                })
                ->modalSubmitActionLabel($isSettlement ? 'تأكيد التسوية' : 'تأكيد التوريد')
                ->modalIcon($isSettlement ? 'heroicon-o-document-check' : 'heroicon-o-check-circle')
                ->modalIconColor($isSettlement ? 'warning' : 'success')
                ->visible(fn () =>
                    $this->record &&
                    $this->record->supply_status !== 'collected' &&
                    $this->record->due_date &&
                    now()->gte($this->record->due_date)
                )
                ->action(function () use ($isSettlement, $netAmount) {
                    // حساب وحفظ القيم
                    $amounts = $this->record->calculateAmountsFromPeriod();

                    $this->record->update([
                        'gross_amount' => $amounts['gross_amount'],
                        'commission_amount' => $amounts['commission_amount'],
                        'maintenance_deduction' => $amounts['maintenance_deduction'],
                        'net_amount' => $amounts['net_amount'],
                        'supply_status' => 'collected',
                        'paid_date' => now(),
                        'collected_by' => auth()->id(),
                    ]);

                    // رسالة النجاح حسب نوع العملية
                    if ($isSettlement) {
                        if ($netAmount < 0) {
                            $message = sprintf(
                                'تم تسجيل دين بقيمة %s ريال على المالك %s',
                                number_format(abs($netAmount), 2),
                                $this->record->owner?->name
                            );
                        } else {
                            $message = sprintf(
                                'تم تأكيد التسوية - لا توجد مستحقات للمالك %s',
                                $this->record->owner?->name
                            );
                        }
                    } else {
                        $message = sprintf(
                            'تم توريد مبلغ %s ريال للمالك %s',
                            number_format($netAmount, 2),
                            $this->record->owner?->name
                        );
                    }

                    \Filament\Notifications\Notification::make()
                        ->title($isSettlement ? 'تم تأكيد التسوية' : 'تم تأكيد التوريد')
                        ->body($message)
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
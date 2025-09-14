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
        return [
            // زر تأكيد التوريد المحدث
            \Filament\Actions\Action::make('confirm_payment')
                ->label('تأكيد التوريد')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تأكيد توريد المبلغ')
                ->modalDescription(function () {
                    // حساب القيمة الفعلية
                    $amounts = $this->record->calculateAmountsFromPeriod();
                    
                    // رسالة بسيطة للتأكيد
                    return sprintf(
                        "أقر أنا %s بتوريد مبلغ %s ريال للمالك %s",
                        auth()->user()->name,
                        number_format($amounts['net_amount'], 2),
                        $this->record->owner?->name ?? 'غير محدد'
                    );
                })
                ->modalSubmitActionLabel('تأكيد التوريد')
                ->modalIcon('heroicon-o-check-circle')
                ->modalIconColor('success')
                ->visible(fn () => 
                    $this->record && 
                    $this->record->supply_status !== 'collected' &&
                    $this->record->due_date && 
                    now()->gte($this->record->due_date)
                )
                ->action(function () {
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
                    
                    \Filament\Notifications\Notification::make()
                        ->title('تم تأكيد التوريد')
                        ->body(sprintf(
                            'تم توريد مبلغ %s ريال للمالك %s',
                            number_format($amounts['net_amount'], 2),
                            $this->record->owner?->name
                        ))
                        ->success()
                        ->send();
                        
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
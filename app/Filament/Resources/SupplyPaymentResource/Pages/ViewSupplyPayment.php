<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use App\Filament\Resources\SupplyPaymentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class ViewSupplyPayment extends ViewRecord
{
    protected static string $resource = SupplyPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // زر التوريد (الدفع)
            \Filament\Actions\Action::make('markAsPaid')
                ->label('توريد')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record && $this->record->supply_status !== 'collected')
                ->requiresConfirmation()
                ->modalHeading('تأكيد التوريد')
                ->modalDescription("هل أنت متأكد من أنك تريد تسجيل هذه الدفعة كمُوردة للمالك {$this->record->owner?->name}؟")
                ->modalSubmitActionLabel('نعم، قم بالتوريد')
                ->form([
                    DatePicker::make('paid_date')
                        ->label('تاريخ التوريد')
                        ->required()
                        ->default(now()),
                    Select::make('collected_by')
                        ->label('الموظف المسؤول')
                        ->options(\App\Models\User::where('type', 'employee')->pluck('name', 'id'))
                        ->required()
                        ->default(auth()->id()),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'supply_status' => 'collected',
                        'paid_date' => $data['paid_date'],
                        'collected_by' => $data['collected_by'],
                        'notes' => $data['notes'] ?? $this->record->notes,
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('تم التوريد بنجاح')
                        ->success()
                        ->send();
                        
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
                
            // زر التأجيل
            \Filament\Actions\Action::make('postpone')
                ->label('تأجيل')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->visible(fn () => $this->record && in_array($this->record->supply_status, ['pending', 'worth_collecting']))
                ->requiresConfirmation()
                ->modalHeading('تأجيل التوريد')
                ->modalDescription("هل تريد تأجيل توريد هذه الدفعة للمالك {$this->record->owner?->name}؟")
                ->modalSubmitActionLabel('تأجيل')
                ->form([
                    DatePicker::make('new_due_date')
                        ->label('تاريخ الاستحقاق الجديد')
                        ->required()
                        ->minDate(now()->addDay()),
                    Textarea::make('postpone_reason')
                        ->label('سبب التأجيل')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'supply_status' => 'pending',
                        'due_date' => $data['new_due_date'],
                        'notes' => ($this->record->notes ? $this->record->notes . "\n" : '') . 
                                 "تأجيل: " . $data['postpone_reason'] . " - " . now()->format('Y-m-d'),
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('تم تأجيل التوريد')
                        ->warning()
                        ->send();
                        
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
<?php

namespace App\Filament\Resources\PropertyContractResource\Pages;

use App\Filament\Resources\PropertyContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePropertyContract extends CreateRecord
{
    protected static string $resource = PropertyContractResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterCreate(): void
    {
        // التحقق من توليد الدفعات التلقائي
        if ($this->record->supplyPayments()->exists()) {
            $count = $this->record->supplyPayments()->count();
            
            \Filament\Notifications\Notification::make()
                ->title('تم توليد الدفعات تلقائياً')
                ->body("تم توليد {$count} دفعة للمالك بنجاح")
                ->success()
                ->duration(5000)
                ->send();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        
        // حساب عدد الدفعات الصحيح قبل الحفظ
        $data['payments_count'] = \App\Services\PropertyContractService::calculatePaymentsCount(
            $data['duration_months'] ?? 0,
            $data['payment_frequency'] ?? 'monthly'
        );
        
        // التأكد من أن القيمة رقمية
        if (!is_numeric($data['payments_count'])) {
            $data['payments_count'] = 0;
        }
        
        return $data;
    }
}
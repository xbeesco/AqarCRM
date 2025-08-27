<?php

namespace App\Filament\Resources\PropertyContractResource\Pages;

use App\Filament\Resources\PropertyContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPropertyContract extends EditRecord
{
    protected static string $resource = PropertyContractResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterSave(): void
    {
        // التحقق من توليد الدفعات التلقائي بعد التحديث
        $paymentsCount = $this->record->supplyPayments()->count();
        
        // التحقق إذا كانت الدفعات جديدة (تم توليدها للتو)
        $recentPayments = $this->record->supplyPayments()
            ->where('created_at', '>=', now()->subSeconds(5))
            ->count();
            
        if ($recentPayments > 0) {
            \Filament\Notifications\Notification::make()
                ->title('تم توليد الدفعات تلقائياً')
                ->body("تم توليد {$recentPayments} دفعة للمالك بعد التحديث")
                ->success()
                ->duration(5000)
                ->send();
        }
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
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
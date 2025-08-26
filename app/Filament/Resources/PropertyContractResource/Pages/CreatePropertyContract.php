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
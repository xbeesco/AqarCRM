<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Filament\Resources\UnitContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnitContract extends EditRecord
{
    protected static string $resource = UnitContractResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
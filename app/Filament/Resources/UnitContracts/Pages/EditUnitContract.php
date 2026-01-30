<?php

namespace App\Filament\Resources\UnitContracts\Pages;

use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Services\PropertyContractService;
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
        // Calculate correct payments count before save
        $data['payments_count'] = PropertyContractService::calculatePaymentsCount(
            $data['duration_months'] ?? 0,
            $data['payment_frequency'] ?? 'monthly'
        );

        // Ensure value is numeric
        if (! is_numeric($data['payments_count'])) {
            $data['payments_count'] = 0;
        }

        return $data;
    }
}

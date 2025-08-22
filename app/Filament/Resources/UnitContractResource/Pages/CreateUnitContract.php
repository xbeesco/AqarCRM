<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Filament\Resources\UnitContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitContract extends CreateRecord
{
    protected static string $resource = UnitContractResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
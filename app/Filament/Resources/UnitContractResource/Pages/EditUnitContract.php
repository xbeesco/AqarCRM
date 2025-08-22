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
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
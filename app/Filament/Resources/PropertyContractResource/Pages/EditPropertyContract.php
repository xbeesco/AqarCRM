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
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
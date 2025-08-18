<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Filament\Resources\UnitContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitContract extends ViewRecord
{
    protected static string $resource = UnitContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
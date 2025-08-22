<?php

namespace App\Filament\Resources\UnitTypes\Pages;

use App\Filament\Resources\UnitTypes\UnitTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUnitTypes extends ManageRecords
{
    protected static string $resource = UnitTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

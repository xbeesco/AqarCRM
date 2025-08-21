<?php

namespace App\Filament\Resources\UnitStatuses\Pages;

use App\Filament\Resources\UnitStatuses\UnitStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUnitStatuses extends ManageRecords
{
    protected static string $resource = UnitStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

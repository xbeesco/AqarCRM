<?php

namespace App\Filament\Resources\UnitContracts\Pages;

use App\Filament\Resources\UnitContracts\UnitContractResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitContracts extends ViewRecord
{
    protected static string $resource = UnitContractResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

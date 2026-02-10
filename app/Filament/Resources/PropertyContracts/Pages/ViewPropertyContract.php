<?php

namespace App\Filament\Resources\PropertyContracts\Pages;

use App\Filament\Resources\PropertyContracts\PropertyContractResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPropertyContract extends ViewRecord
{
    protected static string $resource = PropertyContractResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

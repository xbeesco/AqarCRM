<?php

namespace App\Filament\Resources\PropertyContractResource\Pages;

use App\Filament\Resources\PropertyContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPropertyContract extends ViewRecord
{
    protected static string $resource = PropertyContractResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
<?php

namespace App\Filament\Resources\PropertyTypes\Pages;

use App\Filament\Resources\PropertyTypes\PropertyTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePropertyTypes extends ManageRecords
{
    protected static string $resource = PropertyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

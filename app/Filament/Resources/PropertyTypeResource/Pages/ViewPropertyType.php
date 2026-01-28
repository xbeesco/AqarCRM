<?php

namespace App\Filament\Resources\PropertyTypeResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\PropertyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPropertyType extends ViewRecord
{
    protected static string $resource = PropertyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
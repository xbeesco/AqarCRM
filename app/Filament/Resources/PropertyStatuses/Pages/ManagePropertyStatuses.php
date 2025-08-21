<?php

namespace App\Filament\Resources\PropertyStatuses\Pages;

use App\Filament\Resources\PropertyStatuses\PropertyStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePropertyStatuses extends ManageRecords
{
    protected static string $resource = PropertyStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

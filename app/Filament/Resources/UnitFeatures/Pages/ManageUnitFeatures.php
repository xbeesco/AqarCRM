<?php

namespace App\Filament\Resources\UnitFeatures\Pages;

use App\Filament\Resources\UnitFeatures\UnitFeatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUnitFeatures extends ManageRecords
{
    protected static string $resource = UnitFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

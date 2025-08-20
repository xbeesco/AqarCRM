<?php

namespace App\Filament\Resources\PropertyFeatures\Pages;

use App\Filament\Resources\PropertyFeatures\PropertyFeatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePropertyFeatures extends ManageRecords
{
    protected static string $resource = PropertyFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

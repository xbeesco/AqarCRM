<?php

namespace App\Filament\Resources\UnitFeatureResource\Pages;

use App\Filament\Resources\UnitFeatureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitFeature extends CreateRecord
{
    protected static string $resource = UnitFeatureResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
<?php

namespace App\Filament\Resources\UnitStatusResource\Pages;

use App\Filament\Resources\UnitStatusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitStatus extends CreateRecord
{
    protected static string $resource = UnitStatusResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
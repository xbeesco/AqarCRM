<?php

namespace App\Filament\Resources\PropertyFeatureResource\Pages;

use App\Filament\Resources\PropertyFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPropertyFeature extends ViewRecord
{
    protected static string $resource = PropertyFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
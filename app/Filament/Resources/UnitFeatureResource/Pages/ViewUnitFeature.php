<?php

namespace App\Filament\Resources\UnitFeatureResource\Pages;

use App\Filament\Resources\UnitFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitFeature extends ViewRecord
{
    protected static string $resource = UnitFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل / Edit'),
        ];
    }
}
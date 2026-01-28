<?php

namespace App\Filament\Resources\UnitFeatureResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\UnitFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitFeature extends ViewRecord
{
    protected static string $resource = UnitFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل / Edit'),
        ];
    }
}
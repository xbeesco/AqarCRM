<?php

namespace App\Filament\Resources\UnitStatusResource\Pages;

use App\Filament\Resources\UnitStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitStatus extends ViewRecord
{
    protected static string $resource = UnitStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل / Edit'),
        ];
    }
}
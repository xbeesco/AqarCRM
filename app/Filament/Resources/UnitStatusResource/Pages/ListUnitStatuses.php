<?php

namespace App\Filament\Resources\UnitStatusResource\Pages;

use App\Filament\Resources\UnitStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnitStatuses extends ListRecords
{
    protected static string $resource = UnitStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء حالة جديدة / Create New Status'),
        ];
    }
}
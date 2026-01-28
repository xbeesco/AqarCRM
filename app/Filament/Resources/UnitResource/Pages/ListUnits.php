<?php

namespace App\Filament\Resources\UnitResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\UnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnits extends ListRecords
{
    protected static string $resource = UnitResource::class;

    protected static ?string $title = 'الوحدات';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة وحدة'),
        ];
    }
}

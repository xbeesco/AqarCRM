<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListLocations extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة موقع جديد'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            //LocationResource\Widgets\LocationStatsOverview::class,
        ];
    }
}
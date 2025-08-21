<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLocations extends ManageRecords
{
    protected static string $resource = LocationResource::class;
    
    protected static ?string $title = 'المواقع';
    
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('إضافة موقع جديد')
                ->modalHeading('إضافة موقع جديد')
                ->modalButton('إضافة')
                ->modalWidth('xl'),
        ];
    }
}
<?php

namespace App\Filament\Resources\PropertyStatusResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PropertyStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPropertyStatuses extends ListRecords
{
    protected static string $resource = PropertyStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('حالة عقار جديدة / New Property Status'),
        ];
    }
}
<?php

namespace App\Filament\Resources\OwnerResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\OwnerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOwners extends ListRecords
{
    protected static string $resource = OwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة مالك جديد'),
        ];
    }
}

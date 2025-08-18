<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Filament\Resources\UnitContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnitContracts extends ListRecords
{
    protected static string $resource = UnitContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء عقد إيجار جديد / Create New Unit Contract'),
        ];
    }
}
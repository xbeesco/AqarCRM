<?php

namespace App\Filament\Resources\PropertyContractResource\Pages;

use App\Filament\Resources\PropertyContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPropertyContracts extends ListRecords
{
    protected static string $resource = PropertyContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء عقد ملكية جديد'),
        ];
    }
}
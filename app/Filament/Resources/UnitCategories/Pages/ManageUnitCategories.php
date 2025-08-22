<?php

namespace App\Filament\Resources\UnitCategories\Pages;

use App\Filament\Resources\UnitCategories\UnitCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUnitCategories extends ManageRecords
{
    protected static string $resource = UnitCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\UnitFeatureResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\UnitFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnitFeatures extends ListRecords
{
    protected static string $resource = UnitFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إنشاء ميزة جديدة / Create New Feature'),
        ];
    }
}
<?php

namespace App\Filament\Resources\PropertyFeatureResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PropertyFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPropertyFeatures extends ListRecords
{
    protected static string $resource = PropertyFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ميزة عقار جديدة / New Property Feature'),
        ];
    }
}
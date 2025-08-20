<?php

namespace App\Filament\Resources\NeighborhoodResource\Pages;

use App\Filament\Resources\NeighborhoodResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageNeighborhoods extends ManageRecords
{
    protected static string $resource = NeighborhoodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة منطقة')
                ->icon('heroicon-o-plus'),
        ];
    }
}
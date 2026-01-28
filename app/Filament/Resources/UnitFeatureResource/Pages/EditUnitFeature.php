<?php

namespace App\Filament\Resources\UnitFeatureResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\UnitFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnitFeature extends EditRecord
{
    protected static string $resource = UnitFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض / View'),
            DeleteAction::make()
                ->label('حذف / Delete'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
<?php

namespace App\Filament\Resources\UnitStatusResource\Pages;

use App\Filament\Resources\UnitStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnitStatus extends EditRecord
{
    protected static string $resource = UnitStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض / View'),
            Actions\DeleteAction::make()
                ->label('حذف / Delete'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
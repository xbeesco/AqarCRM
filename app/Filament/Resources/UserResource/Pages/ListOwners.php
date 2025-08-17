<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOwners extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'الملاك';

    protected static ?string $navigationLabel = 'الملاك';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة مالك جديد')
                ->mutateFormDataUsing(function (array $data): array {
                    // Automatically assign owner role
                    $data['roles'] = ['owner'];
                    return $data;
                }),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->owners();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // يمكن إضافة widgets إحصائية للملاك هنا
        ];
    }
}
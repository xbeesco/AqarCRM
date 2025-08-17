<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTenants extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'المستأجرين';

    protected static ?string $navigationLabel = 'المستأجرين';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة مستأجر جديد')
                ->mutateFormDataUsing(function (array $data): array {
                    // Automatically assign tenant role
                    $data['roles'] = ['tenant'];
                    return $data;
                }),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->tenants();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // يمكن إضافة widgets إحصائية للمستأجرين هنا
        ];
    }
}
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'employees' => Tab::make('الموظفين')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', function($q) {
                    $q->where('name', 'employee');
                }))
                ->badge(fn () => \App\Models\User::employees()->count()),
            
            'owners' => Tab::make('الملاك')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', function($q) {
                    $q->where('name', 'owner');
                }))
                ->badge(fn () => \App\Models\User::owners()->count())
                ->url(fn () => UserResource::getUrl('owners')),
            
            'tenants' => Tab::make('المستأجرين')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', function($q) {
                    $q->where('name', 'tenant');
                }))
                ->badge(fn () => \App\Models\User::tenants()->count())
                ->url(fn () => UserResource::getUrl('tenants')),
        ];
    }

    public function getDefaultActiveTab(): ?string
    {
        return 'employees';
    }
}
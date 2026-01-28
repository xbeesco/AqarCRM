<?php

namespace App\Filament\Resources\CollectionPaymentResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CollectionPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCollectionPayment extends ViewRecord
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\CollectionPayment\CollectionPaymentResource\Pages;

use App\Filament\Resources\CollectionPayment\CollectionPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCollectionPayment extends ViewRecord
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
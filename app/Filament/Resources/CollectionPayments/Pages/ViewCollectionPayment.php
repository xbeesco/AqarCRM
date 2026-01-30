<?php

namespace App\Filament\Resources\CollectionPayments\Pages;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use Filament\Actions\EditAction;
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

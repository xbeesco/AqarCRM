<?php

namespace App\Filament\Resources\CollectionPayment\CollectionPaymentResource\Pages;

use App\Filament\Resources\CollectionPayment\CollectionPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCollectionPayment extends CreateRecord
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
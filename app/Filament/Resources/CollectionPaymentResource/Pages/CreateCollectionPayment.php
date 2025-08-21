<?php

namespace App\Filament\Resources\CollectionPaymentResource\Pages;

use App\Filament\Resources\CollectionPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCollectionPayment extends CreateRecord
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
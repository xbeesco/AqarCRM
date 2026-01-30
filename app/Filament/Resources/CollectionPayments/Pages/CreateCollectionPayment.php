<?php

namespace App\Filament\Resources\CollectionPayments\Pages;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCollectionPayment extends CreateRecord
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}

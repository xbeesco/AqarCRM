<?php

namespace App\Filament\Resources\CollectionPayments\Pages;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use Filament\Resources\Pages\EditRecord;

class EditCollectionPayment extends EditRecord
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}

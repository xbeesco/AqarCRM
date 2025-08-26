<?php

namespace App\Filament\Resources\CollectionPaymentResource\Pages;

use App\Filament\Resources\CollectionPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCollectionPayment extends EditRecord
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    public function getMaxContentWidth(): ?string
    {
        return 'full'; // يجعل المحتوى يأخذ العرض الكامل
    }
}
<?php

namespace App\Filament\Resources\CollectionPayment\CollectionPaymentResource\Pages;

use App\Filament\Resources\CollectionPayment\CollectionPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCollectionPayments extends ListRecords
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Collection Payment / دفعة تحصيل جديدة'),
        ];
    }
}
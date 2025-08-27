<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use App\Filament\Resources\SupplyPaymentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;

class ListSupplyPayments extends ListRecords
{
    protected static string $resource = SupplyPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('دفعة توريد جديدة'),
        ];
    }
    
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Check if property_contract_id is in the request
        $contractId = request()->get('property_contract_id');
        
        if ($contractId) {
            $query->where('property_contract_id', $contractId);
        }
        
        return $query;
    }
}
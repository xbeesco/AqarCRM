<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use App\Filament\Resources\SupplyPaymentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\SupplyPaymentsExport;
use Maatwebsite\Excel\Facades\Excel;

class ListSupplyPayments extends ListRecords
{
    protected static string $resource = SupplyPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filename = 'دفعات-التوريد-' . date('Y-m-d') . '.xlsx';
                    
                    return Excel::download(new SupplyPaymentsExport, $filename);
                }),
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
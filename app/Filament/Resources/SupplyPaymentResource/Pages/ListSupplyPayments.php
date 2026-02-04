<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use App\Exports\SupplyPaymentsExport;
use App\Filament\Resources\SupplyPaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListSupplyPayments extends ListRecords
{
    protected static string $resource = SupplyPaymentResource::class;

    protected ?int $contractId = null;

    protected ?int $propertyId = null;

    protected ?int $ownerId = null;

    public function mount(): void
    {
        parent::mount();

        // Get filter parameters from URL
        $this->contractId = request()->integer('property_contract_id') ?: null;
        $this->propertyId = request()->integer('property_id') ?: null;
        $this->ownerId = request()->integer('owner_id') ?: null;

        if ($this->propertyId) {
            $this->tableFilters['property']['values'] = [$this->propertyId];
        }

        if ($this->ownerId) {
            $this->tableFilters['owner_id']['value'] = $this->ownerId;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filename = 'دفعات-التوريد-'.date('Y-m-d').'.xlsx';

                    return Excel::download(new SupplyPaymentsExport, $filename);
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // Check if property_contract_id is in the request (legacy support)
        if ($this->contractId) {
            $query->where('property_contract_id', $this->contractId);
        }

        return $query;
    }
}

<?php

namespace App\Filament\Resources\PropertyContracts\Pages;

use App\Exports\PropertyContractsExport;
use App\Filament\Resources\PropertyContracts\PropertyContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListPropertyContracts extends ListRecords
{
    protected static string $resource = PropertyContractResource::class;

    protected ?int $propertyId = null;

    protected ?int $ownerId = null;

    public function mount(): void
    {
        parent::mount();

        // Get filter parameters from URL (support both simple and Filament formats)
        $this->propertyId = request()->integer('property_id') ?: request()->input('tableFilters.property_id.value') ?: null;
        $this->ownerId = request()->integer('owner_id') ?: request()->input('tableFilters.owner.owner_id') ?: null;

        if ($this->propertyId) {
            $this->tableFilters['property_id']['value'] = $this->propertyId;
        }

        if ($this->ownerId) {
            $this->tableFilters['owner']['owner_id'] = $this->ownerId;
        }
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة عقد'),
            Actions\Action::make('export')
                ->label('تصدير')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filename = 'عقود-الملاك-'.date('Y-m-d').'.xlsx';

                    return Excel::download(new PropertyContractsExport, $filename);
                }),
        ];
    }
}

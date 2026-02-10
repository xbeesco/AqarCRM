<?php

namespace App\Filament\Resources\UnitContractResource\Pages;

use App\Exports\UnitContractsExport;
use App\Filament\Resources\UnitContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListUnitContracts extends ListRecords
{
    protected static string $resource = UnitContractResource::class;

    protected ?int $propertyId = null;

    protected ?int $ownerId = null;

    protected ?int $tenantId = null;

    protected ?int $unitId = null;

    public function mount(): void
    {
        parent::mount();

        // Get filter parameters from URL
        $this->propertyId = request()->integer('property_id') ?: null;
        $this->ownerId = request()->integer('owner_id') ?: null;
        $this->tenantId = request()->integer('tenant_id') ?: null;
        $this->unitId = request()->integer('unit_id') ?: null;

        if ($this->propertyId) {
            $this->tableFilters['property']['values'] = [$this->propertyId];
        }

        if ($this->ownerId) {
            $this->tableFilters['owner']['owner_id'] = $this->ownerId;
        }

        if ($this->tenantId) {
            $this->tableFilters['tenant_id']['value'] = $this->tenantId;
        }

        if ($this->unitId) {
            $this->tableFilters['unit_id']['value'] = $this->unitId;
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
                    $filename = 'عقود-المستأجرين-'.date('Y-m-d').'.xlsx';

                    return Excel::download(new UnitContractsExport, $filename);
                }),
        ];
    }
}

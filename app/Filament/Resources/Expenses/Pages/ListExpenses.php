<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected ?int $propertyId = null;

    protected ?int $unitId = null;

    protected ?int $ownerId = null;

    public function mount(): void
    {
        parent::mount();

        // Get filter parameters from URL (support both simple and Filament formats)
        $this->propertyId = request()->integer('property_id') ?: request()->input('tableFilters.property_and_unit.property_id') ?: null;
        $this->unitId = request()->integer('unit_id') ?: request()->input('tableFilters.property_and_unit.unit_id') ?: null;
        $this->ownerId = request()->integer('owner_id') ?: request()->input('tableFilters.owner_id.value') ?: null;

        // If unit_id is provided without property_id, get property_id from unit
        if ($this->unitId && ! $this->propertyId) {
            $unit = \App\Models\Unit::find($this->unitId);
            if ($unit) {
                $this->propertyId = $unit->property_id;
            }
        }

        if ($this->propertyId) {
            $this->tableFilters['property_and_unit']['property_id'] = $this->propertyId;
            if ($this->unitId) {
                $this->tableFilters['property_and_unit']['unit_id'] = $this->unitId;
            }
        }

        if ($this->ownerId) {
            $this->tableFilters['owner_id']['value'] = $this->ownerId;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة نفقة'),
        ];
    }
}

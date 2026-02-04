<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
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

        // Get filter parameters from URL
        $this->propertyId = request()->integer('property_id') ?: null;
        $this->unitId = request()->integer('unit_id') ?: null;
        $this->ownerId = request()->integer('owner_id') ?: null;

        if ($this->propertyId) {
            $this->tableFilters['property_and_unit']['property_id'] = $this->propertyId;
            if ($this->unitId) {
                $this->tableFilters['property_and_unit']['unit_id'] = $this->unitId;
            }
        }

        if ($this->ownerId) {
            $this->tableFilters['owner']['owner_id'] = $this->ownerId;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة نفقة'),
        ];
    }
}

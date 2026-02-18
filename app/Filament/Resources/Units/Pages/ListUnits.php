<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUnits extends ListRecords
{
    protected static string $resource = UnitResource::class;

    protected static ?string $title = 'الوحدات';

    protected ?int $propertyId = null;

    protected ?int $ownerId = null;

    protected ?int $tenantId = null;

    public function mount(): void
    {
        parent::mount();

        // Get filter parameters from URL (support both simple and Filament formats)
        $this->propertyId = request()->integer('property_id') ?: request()->input('tableFilters.property_id.value') ?: null;
        $this->ownerId = request()->integer('owner_id') ?: request()->input('tableFilters.owner_id.value') ?: null;
        $this->tenantId = request()->integer('tenant_id') ?: request()->input('tableFilters.tenant_id.value') ?: null;

        if ($this->propertyId) {
            $this->tableFilters['property_id']['value'] = $this->propertyId;
        }

        if ($this->ownerId) {
            $this->tableFilters['owner_id']['value'] = $this->ownerId;
        }

        if ($this->tenantId) {
            $this->tableFilters['tenant_id']['value'] = $this->tenantId;
        }
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة وحدة'),
        ];
    }
}

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

        // Get filter parameters from URL
        $this->propertyId = request()->integer('property_id') ?: null;
        $this->ownerId = request()->integer('owner_id') ?: null;
        $this->tenantId = request()->integer('tenant_id') ?: null;

        if ($this->propertyId) {
            $this->tableFilters['property']['values'] = [$this->propertyId];
        }

        if ($this->ownerId) {
            $this->tableFilters['owner']['owner_id'] = $this->ownerId;
        }

        if ($this->tenantId) {
            $this->tableFilters['tenant']['tenant_id'] = $this->tenantId;
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

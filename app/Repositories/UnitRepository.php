<?php

namespace App\Repositories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UnitRepository
{
    public function __construct(
        protected Unit $model
    ) {}

    /**
     * Create a new unit
     */
    public function create(array $data): Unit
    {
        return $this->model->create($data);
    }

    /**
     * Find unit by ID or fail
     */
    public function findOrFail(int $id): Unit
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Find unit with full details
     */
    public function findWithFullDetails(int $id): Unit
    {
        return $this->model->with([
            'property',
            'status',
            'currentTenant',
            'features',
        ])->findOrFail($id);
    }

    /**
     * Get available units query
     */
    public function getAvailableUnitsQuery(): Builder
    {
        return $this->model->available()
            ->active()
            ->with(['property', 'status', 'currentTenant']);
    }

    /**
     * Get units by property
     */
    public function getUnitsByProperty(int $propertyId, bool $includeRelations = true): Collection
    {
        $query = $this->model->where('property_id', $propertyId);

        if ($includeRelations) {
            $query->with(['status', 'currentTenant', 'features']);
        }

        return $query->get();
    }

    /**
     * Search units with multiple criteria
     */
    public function searchUnits(array $criteria): Collection
    {
        $query = $this->model->query();

        // Search by unit number
        if (! empty($criteria['unit_number'])) {
            $query->where('unit_number', 'like', '%'.$criteria['unit_number'].'%');
        }

        // Search by property name
        if (! empty($criteria['property_name'])) {
            $query->whereHas('property', function ($q) use ($criteria) {
                $q->where('name', 'like', '%'.$criteria['property_name'].'%');
            });
        }

        // Search by tenant name
        if (! empty($criteria['tenant_name'])) {
            $query->whereHas('currentTenant', function ($q) use ($criteria) {
                $q->where('name', 'like', '%'.$criteria['tenant_name'].'%');
            });
        }

        // Price range
        if (! empty($criteria['min_price'])) {
            $query->where('rent_price', '>=', $criteria['min_price']);
        }

        if (! empty($criteria['max_price'])) {
            $query->where('rent_price', '<=', $criteria['max_price']);
        }

        // Area range
        if (! empty($criteria['min_area'])) {
            $query->where('area_sqm', '>=', $criteria['min_area']);
        }

        if (! empty($criteria['max_area'])) {
            $query->where('area_sqm', '<=', $criteria['max_area']);
        }

        return $query->with(['property', 'status', 'currentTenant'])->get();
    }

    /**
     * Get nearby units (if coordinates were available)
     */
    public function getNearbyUnits(float $lat, float $lng, float $radius): Collection
    {
        // This would require geospatial queries if coordinates are stored
        // For now, return empty collection
        return collect();
    }

    /**
     * Get units requiring maintenance
     */
    public function getUnitsRequiringMaintenance(?string $maintenanceType = null, ?string $dueDate = null): Collection
    {
        $query = $this->model->whereHas('status', function ($q) {
            $q->where('requires_maintenance', true);
        });

        if ($dueDate) {
            $query->where('next_maintenance_date', '<=', $dueDate);
        }

        return $query->with(['property', 'status'])->get();
    }

    /**
     * Update unit
     */
    public function update(int $id, array $data): bool
    {
        $unit = $this->findOrFail($id);

        return $unit->update($data);
    }

    /**
     * Delete unit
     */
    public function delete(int $id): bool
    {
        $unit = $this->findOrFail($id);

        return $unit->delete();
    }

    /**
     * Get units statistics
     */
    public function getUnitsStatistics(array $filters = []): array
    {
        $query = $this->model->query();

        // Apply property filter if provided
        if (! empty($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        $total = $query->count();
        $available = $query->clone()->available()->count();
        $occupied = $query->clone()->occupied()->count();
        $maintenance = $query->clone()->whereHas('status', function ($q) {
            $q->where('requires_maintenance', true);
        })->count();

        $averageRent = $query->clone()->avg('rent_price');
        $totalArea = $query->clone()->sum('area_sqm');

        return [
            'total_units' => $total,
            'available_units' => $available,
            'occupied_units' => $occupied,
            'maintenance_units' => $maintenance,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 2) : 0,
            'average_rent' => round($averageRent, 2),
            'total_area' => $totalArea,
        ];
    }
}

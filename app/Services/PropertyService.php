<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyFeature;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PropertyService
{
    /**
     * Create property with features
     */
    public function createPropertyWithFeatures(array $data, array $features = []): Property
    {
        return DB::transaction(function () use ($data, $features) {
            $property = Property::create($data);

            if (! empty($features)) {
                $this->attachFeatures($property, $features);
            }

            return $property->load(['features', 'owner', 'location']);
        });
    }

    /**
     * Update property status
     */
    public function updatePropertyStatus(int $propertyId, string $status): bool
    {
        $property = Property::findOrFail($propertyId);

        return $property->update(['status' => $status]);
    }

    /**
     * Calculate property metrics
     */
    public function calculatePropertyMetrics(int $propertyId): array
    {
        $property = Property::with(['units'])->findOrFail($propertyId);

        $totalUnits = $property->units->count();
        $occupiedUnits = $property->units->where('current_tenant_id', '!=', null)->count();
        $availableUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? ($occupiedUnits / $totalUnits) * 100 : 0;
        $monthlyRevenue = $property->units->whereNotNull('current_tenant_id')->sum('rent_price');

        return [
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'available_units' => $availableUnits,
            'occupancy_rate' => round($occupancyRate, 2),
            'monthly_revenue' => $monthlyRevenue,
            'annual_revenue' => $monthlyRevenue * 12,
        ];
    }

    /**
     * Generate property report
     */
    public function generatePropertyReport(int $propertyId, string $period = 'monthly'): array
    {
        $property = Property::with(['units.activeContract.tenant', 'owner'])->findOrFail($propertyId);
        $metrics = $this->calculatePropertyMetrics($propertyId);

        $startDate = match ($period) {
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'quarterly' => Carbon::now()->startOfQuarter(),
            'yearly' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        return [
            'property' => $property,
            'metrics' => $metrics,
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => Carbon::now(),
            'units_details' => $this->getUnitsDetails($property),
        ];
    }

    /**
     * Search properties with filters
     */
    public function searchProperties(array $filters): Collection
    {
        $query = Property::with(['owner', 'location', 'units']);

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['min_area'])) {
            $query->where('area_sqm', '>=', $filters['min_area']);
        }

        if (isset($filters['max_area'])) {
            $query->where('area_sqm', '<=', $filters['max_area']);
        }

        if (isset($filters['has_elevator'])) {
            $query->where('has_elevator', $filters['has_elevator']);
        }

        if (isset($filters['min_parking_spots'])) {
            $query->where('parking_spots', '>=', $filters['min_parking_spots']);
        }

        return $query->get();
    }

    /**
     * Get nearby properties
     */
    public function getNearbyProperties(float $latitude, float $longitude, float $radiusKm = 5): Collection
    {
        // Using haversine formula for distance calculation
        return Property::selectRaw('
                *,
                (
                    6371 * acos(
                        cos(radians(?)) * 
                        cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * 
                        sin(radians(latitude))
                    )
                ) AS distance_km
            ', [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->havingRaw('distance_km <= ?', [$radiusKm])
            ->orderBy('distance_km')
            ->with(['owner', 'location'])
            ->get();
    }

    /**
     * Attach features to property
     */
    private function attachFeatures(Property $property, array $features): void
    {
        $featureData = [];

        foreach ($features as $featureId => $value) {
            $feature = PropertyFeature::find($featureId);
            if ($feature && $feature->isValidValue($value)) {
                $featureData[$featureId] = ['value' => $feature->getFormattedValue($value)];
            }
        }

        $property->features()->sync($featureData);
    }

    /**
     * Get units details for property
     */
    private function getUnitsDetails(Property $property): array
    {
        return $property->units->map(function ($unit) {
            return [
                'unit_number' => $unit->name,
                'floor_number' => $unit->floor_number,
                'area_sqm' => $unit->area_sqm,
                'rent_price' => $unit->rent_price,
                'current_tenant' => $unit->current_tenant?->name,
                'is_available' => $unit->isAvailable(),
                'status' => $unit->unitType?->name_ar,
            ];
        })->toArray();
    }

    /**
     * Get property portfolio summary
     */
    public function getPortfolioSummary(int $ownerId): array
    {
        $properties = Property::where('owner_id', $ownerId)->with(['units'])->get();

        $totalProperties = $properties->count();
        $totalUnits = $properties->sum(fn ($property) => $property->units->count());
        $occupiedUnits = $properties->sum(fn ($property) => $property->units->whereNotNull('current_tenant_id')->count());
        $totalRevenue = $properties->sum(fn ($property) => $property->units->whereNotNull('current_tenant_id')->sum('rent_price'));
        $averageOccupancy = $totalUnits > 0 ? ($occupiedUnits / $totalUnits) * 100 : 0;

        return [
            'total_properties' => $totalProperties,
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'available_units' => $totalUnits - $occupiedUnits,
            'occupancy_rate' => round($averageOccupancy, 2),
            'monthly_revenue' => $totalRevenue,
            'annual_revenue' => $totalRevenue * 12,
            'properties' => $properties->map(fn ($property) => [
                'id' => $property->id,
                'name' => $property->name,
                'units_count' => $property->units->count(),
                'occupancy_rate' => $property->occupancy_rate,
                'monthly_revenue' => $property->monthly_revenue,
            ])->toArray(),
        ];
    }
}

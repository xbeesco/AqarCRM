<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\User;
use App\Models\Property;
use App\Models\UnitStatus;
use App\Repositories\UnitRepository;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class UnitService
{
    public function __construct(
        protected UnitRepository $unitRepository
    ) {}

    /**
     * Create a new unit with property validation
     */
    public function createUnit(array $data): Unit
    {
        // Validate property exists and user has permission
        $property = Property::findOrFail($data['property_id']);
        
        // Check if unit number is unique within property
        $this->validateUniqueUnitNumber($data['property_id'], $data['unit_number']);
        
        // Set default status to available if not provided
        if (!isset($data['status_id'])) {
            $availableStatus = UnitStatus::where('slug', 'available')->first();
            $data['status_id'] = $availableStatus?->id;
        }
        
        // Auto-generate unit code if needed
        $data['unit_code'] = $this->generateUnitCode($data['property_id'], $data['unit_number']);
        
        return $this->unitRepository->create($data);
    }

    /**
     * Assign tenant to unit
     */
    public function assignTenant(int $unitId, int $tenantId, ?string $startDate = null): bool
    {
        $unit = $this->unitRepository->findOrFail($unitId);
        $tenant = User::findOrFail($tenantId);
        
        // Validate unit is available
        if (!$unit->isAvailable()) {
            throw new \Exception('Unit is not available for assignment');
        }
        
        // Validate tenant exists and has no active rental
        if ($tenant->hasRole('tenant') && $this->tenantHasActiveRental($tenant)) {
            throw new \Exception('Tenant already has an active rental');
        }
        
        return $unit->assignTenant($tenant, $startDate);
    }

    /**
     * Release tenant from unit
     */
    public function releaseTenant(int $unitId, ?string $endDate = null, ?string $reason = null): bool
    {
        $unit = $this->unitRepository->findOrFail($unitId);
        
        if (!$unit->isOccupied()) {
            throw new \Exception('Unit has no current tenant');
        }
        
        return $unit->releaseTenant($endDate);
    }

    /**
     * Update unit status with validation
     */
    public function updateUnitStatus(int $unitId, int $newStatusId, ?string $reason = null): bool
    {
        $unit = $this->unitRepository->findOrFail($unitId);
        $newStatus = UnitStatus::findOrFail($newStatusId);
        
        // Validate status transition
        if (!$unit->status->canTransitionTo($newStatus)) {
            throw new \Exception('Invalid status transition');
        }
        
        // Check tenant occupancy rules
        if ($newStatus->slug === 'available' && $unit->isOccupied()) {
            throw new \Exception('Cannot set status to available while unit is occupied');
        }
        
        $unit->status_id = $newStatusId;
        
        // Update availability dates if needed
        if ($newStatus->is_available && !$unit->available_from) {
            $unit->available_from = now()->toDateString();
        }
        
        return $unit->save();
    }

    /**
     * Check unit availability for specific date range
     */
    public function checkAvailability(int $unitId, string $startDate, string $endDate): array
    {
        $unit = $this->unitRepository->findOrFail($unitId);
        
        $isAvailable = $unit->isAvailable();
        $nextAvailableDate = null;
        
        if (!$isAvailable) {
            // Calculate next available date based on current occupancy
            if ($unit->isOccupied()) {
                // Would need contract information to determine end date
                $nextAvailableDate = $unit->available_from;
            } elseif ($unit->isUnderMaintenance()) {
                $nextAvailableDate = $unit->next_maintenance_date;
            }
        }
        
        return [
            'available' => $isAvailable,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_available_date' => $nextAvailableDate,
            'status' => $unit->status->name,
            'current_tenant' => $unit->currentTenant?->name,
        ];
    }

    /**
     * Calculate rental pricing for different periods with discounts
     */
    public function calculatePricing(int $unitId, string $periodType, int $duration, float $discountPercentage = 0): array
    {
        $unit = $this->unitRepository->findOrFail($unitId);
        
        $baseRent = $unit->rent_price;
        $totalAmount = $unit->calculatePrice($periodType) * $duration;
        
        // Apply discount
        $discountAmount = $totalAmount * ($discountPercentage / 100);
        $finalAmount = $totalAmount - $discountAmount;
        
        // Calculate taxes and fees (example: 15% VAT)
        $vatRate = 0.15;
        $vatAmount = $finalAmount * $vatRate;
        $totalWithVat = $finalAmount + $vatAmount;
        
        return [
            'base_rent' => $baseRent,
            'period_type' => $periodType,
            'duration' => $duration,
            'subtotal' => $totalAmount,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'amount_after_discount' => $finalAmount,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'total_amount' => $totalWithVat,
        ];
    }

    /**
     * Get available units with optional filtering
     */
    public function getAvailableUnits(array $filters = [], array $sortOptions = []): Collection
    {
        $query = $this->unitRepository->getAvailableUnitsQuery();
        
        // Apply filters
        if (!empty($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }
        
        if (!empty($filters['unit_type'])) {
            $query->where('unit_type', $filters['unit_type']);
        }
        
        if (!empty($filters['min_price'])) {
            $query->where('rent_price', '>=', $filters['min_price']);
        }
        
        if (!empty($filters['max_price'])) {
            $query->where('rent_price', '<=', $filters['max_price']);
        }
        
        if (!empty($filters['rooms_count'])) {
            $query->where('rooms_count', $filters['rooms_count']);
        }
        
        if (!empty($filters['min_area'])) {
            $query->where('area_sqm', '>=', $filters['min_area']);
        }
        
        if (!empty($filters['max_area'])) {
            $query->where('area_sqm', '<=', $filters['max_area']);
        }
        
        if (isset($filters['furnished'])) {
            $query->where('furnished', $filters['furnished']);
        }
        
        // Apply sorting
        $sortBy = $sortOptions['sort_by'] ?? 'unit_number';
        $sortDirection = $sortOptions['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);
        
        return $query->get();
    }

    /**
     * Get units by property with details
     */
    public function getUnitsByProperty(int $propertyId, bool $includeRelations = true): Collection
    {
        return $this->unitRepository->getUnitsByProperty($propertyId, $includeRelations);
    }

    /**
     * Search units with multiple criteria
     */
    public function searchUnits(array $searchCriteria): Collection
    {
        return $this->unitRepository->searchUnits($searchCriteria);
    }

    /**
     * Generate unique unit code
     */
    protected function generateUnitCode(int $propertyId, string $unitNumber): string
    {
        $property = Property::find($propertyId);
        $propertyCode = $property ? "PROP{$property->id}" : "PROP{$propertyId}";
        
        return "{$propertyCode}-U{$unitNumber}";
    }

    /**
     * Validate unit number is unique within property
     */
    protected function validateUniqueUnitNumber(int $propertyId, string $unitNumber, ?int $excludeUnitId = null): void
    {
        $query = Unit::where('property_id', $propertyId)
                     ->where('unit_number', $unitNumber);
        
        if ($excludeUnitId) {
            $query->where('id', '!=', $excludeUnitId);
        }
        
        if ($query->exists()) {
            throw new \Exception('Unit number already exists in this property');
        }
    }

    /**
     * Check if tenant has active rental
     */
    protected function tenantHasActiveRental(User $tenant): bool
    {
        return Unit::where('current_tenant_id', $tenant->id)
                   ->where('is_active', true)
                   ->exists();
    }
}
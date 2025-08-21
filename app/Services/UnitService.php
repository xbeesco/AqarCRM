<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\User;
use App\Models\UnitStatus;
use App\Models\UnitFeature;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnitService
{
    /**
     * Check unit availability for date range
     */
    public function checkUnitAvailability(int $unitId, array $dateRange = []): bool
    {
        $unit = Unit::with(['status'])->findOrFail($unitId);
        
        if (!$unit->isAvailable()) {
            return false;
        }
        
        // If date range is provided, check availability for that period
        if (!empty($dateRange)) {
            $startDate = Carbon::parse($dateRange['start'] ?? now());
            $endDate = Carbon::parse($dateRange['end'] ?? now()->addMonth());
            
            // Check if unit will be available for the requested period
            if ($unit->available_from && Carbon::parse($unit->available_from)->gt($startDate)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Assign tenant to unit with contract data
     */
    public function assignTenant(int $unitId, int $tenantId, array $contractData = []): array
    {
        return DB::transaction(function () use ($unitId, $tenantId, $contractData) {
            $unit = Unit::findOrFail($unitId);
            $tenant = User::findOrFail($tenantId);
            
            if (!$unit->isAvailable()) {
                throw new \Exception('Unit is not available for assignment');
            }
            
            // Assign tenant to unit
            $success = $unit->assignTenant($tenant, $contractData['start_date'] ?? null);
            
            if (!$success) {
                throw new \Exception('Failed to assign tenant to unit');
            }
            
            // Update unit availability
            $unit->update([
                'available_from' => null,
            ]);
            
            return [
                'success' => true,
                'unit' => $unit->fresh(['property', 'currentTenant', 'status']),
                'tenant' => $tenant,
                'message' => 'Tenant assigned successfully',
            ];
        });
    }

    /**
     * Release unit from tenant
     */
    public function releaseUnit(int $unitId, string $reason = ''): array
    {
        return DB::transaction(function () use ($unitId, $reason) {
            $unit = Unit::with(['currentTenant'])->findOrFail($unitId);
            
            if (!$unit->isOccupied()) {
                throw new \Exception('Unit is not currently occupied');
            }
            
            $previousTenant = $unit->currentTenant;
            
            // Release tenant from unit
            $success = $unit->releaseTenant();
            
            if (!$success) {
                throw new \Exception('Failed to release unit');
            }
            
            return [
                'success' => true,
                'unit' => $unit->fresh(['property', 'status']),
                'previous_tenant' => $previousTenant,
                'reason' => $reason,
                'message' => 'Unit released successfully',
            ];
        });
    }

    /**
     * Calculate unit pricing for different durations
     */
    public function calculateUnitPricing(int $unitId, string $duration = 'monthly'): array
    {
        $unit = Unit::findOrFail($unitId);
        
        $basePrice = $unit->rent_price;
        $discounts = $this->getPricingDiscounts($duration);
        
        $prices = [];
        
        foreach (['monthly', 'quarterly', 'semi_annual', 'annual'] as $period) {
            $periodPrice = $unit->calculatePrice($period);
            $discount = $discounts[$period] ?? 0;
            $discountedPrice = $periodPrice * (1 - $discount / 100);
            
            $prices[$period] = [
                'base_price' => $periodPrice,
                'discount_percentage' => $discount,
                'discount_amount' => $periodPrice - $discountedPrice,
                'final_price' => $discountedPrice,
                'monthly_equivalent' => $this->getMonthlyEquivalent($discountedPrice, $period),
            ];
        }
        
        return [
            'unit' => $unit,
            'pricing' => $prices,
            'recommended_period' => $this->getRecommendedPeriod($prices),
        ];
    }

    /**
     * Search units with advanced filters
     */
    public function searchUnits(array $filters): Collection
    {
        $query = Unit::with(['property', 'currentTenant', 'status', 'features']);
        
        // Property filter
        if (isset($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }
        
        // Status filter
        if (isset($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }
        
        // Availability filter
        if (isset($filters['availability'])) {
            if ($filters['availability'] === 'available') {
                $query->whereNull('current_tenant_id');
            } elseif ($filters['availability'] === 'occupied') {
                $query->whereNotNull('current_tenant_id');
            }
        }
        
        // Unit type filter
        if (isset($filters['unit_type'])) {
            $query->where('unit_type', $filters['unit_type']);
        }
        
        // Price range filter
        if (isset($filters['min_price'])) {
            $query->where('rent_price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('rent_price', '<=', $filters['max_price']);
        }
        
        // Area range filter
        if (isset($filters['min_area'])) {
            $query->where('area_sqm', '>=', $filters['min_area']);
        }
        if (isset($filters['max_area'])) {
            $query->where('area_sqm', '<=', $filters['max_area']);
        }
        
        // Rooms count filter
        if (isset($filters['rooms_count'])) {
            $query->where('rooms_count', $filters['rooms_count']);
        }
        
        // Features filter
        if (isset($filters['features'])) {
            foreach ($filters['features'] as $feature => $required) {
                if ($required) {
                    $query->where($feature, true);
                }
            }
        }
        
        // Floor range filter
        if (isset($filters['min_floor'])) {
            $query->where('floor_number', '>=', $filters['min_floor']);
        }
        if (isset($filters['max_floor'])) {
            $query->where('floor_number', '<=', $filters['max_floor']);
        }
        
        return $query->get();
    }

    /**
     * Get unit recommendations based on criteria
     */
    public function getUnitRecommendations(array $criteria): Collection
    {
        $query = Unit::with(['property', 'status'])
            ->where('is_active', true)
            ->whereNull('current_tenant_id')
            ->whereHas('status', function ($q) {
                $q->where('is_available', true);
            });
        
        // Apply criteria filters
        if (isset($criteria['max_budget'])) {
            $query->where('rent_price', '<=', $criteria['max_budget']);
        }
        
        if (isset($criteria['min_rooms'])) {
            $query->where('rooms_count', '>=', $criteria['min_rooms']);
        }
        
        if (isset($criteria['preferred_area'])) {
            $query->whereHas('property', function ($q) use ($criteria) {
                $q->where('location_id', $criteria['preferred_area']);
            });
        }
        
        if (isset($criteria['unit_type'])) {
            $query->where('unit_type', $criteria['unit_type']);
        }
        
        if (isset($criteria['furnished'])) {
            $query->where('furnished', $criteria['furnished']);
        }
        
        // Sort by relevance score
        return $query->get()->map(function ($unit) use ($criteria) {
            $unit->relevance_score = $this->calculateRelevanceScore($unit, $criteria);
            return $unit;
        })->sortByDesc('relevance_score');
    }

    /**
     * Get units performance metrics
     */
    public function getUnitsPerformanceMetrics(array $unitIds = []): array
    {
        $query = Unit::with(['property']);
        
        if (!empty($unitIds)) {
            $query->whereIn('id', $unitIds);
        }
        
        $units = $query->get();
        
        $totalUnits = $units->count();
        $occupiedUnits = $units->whereNotNull('current_tenant_id')->count();
        $availableUnits = $totalUnits - $occupiedUnits;
        $totalRevenue = $units->whereNotNull('current_tenant_id')->sum('rent_price');
        $averageRent = $units->avg('rent_price');
        $averageArea = $units->avg('area_sqm');
        $pricePerSqm = $averageArea > 0 ? $averageRent / $averageArea : 0;
        
        return [
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'available_units' => $availableUnits,
            'occupancy_rate' => $totalUnits > 0 ? ($occupiedUnits / $totalUnits) * 100 : 0,
            'total_monthly_revenue' => $totalRevenue,
            'average_rent' => $averageRent,
            'average_area_sqm' => $averageArea,
            'price_per_sqm' => $pricePerSqm,
            'unit_type_distribution' => $this->getUnitTypeDistribution($units),
            'floor_distribution' => $this->getFloorDistribution($units),
        ];
    }

    /**
     * Get pricing discounts for different periods
     */
    private function getPricingDiscounts(string $duration): array
    {
        return [
            'monthly' => 0,
            'quarterly' => 5,    // 5% discount for quarterly payment
            'semi_annual' => 8,  // 8% discount for semi-annual payment
            'annual' => 12,      // 12% discount for annual payment
        ];
    }

    /**
     * Get monthly equivalent price
     */
    private function getMonthlyEquivalent(float $totalPrice, string $period): float
    {
        $months = match ($period) {
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1,
        };
        
        return $months > 0 ? $totalPrice / $months : $totalPrice;
    }

    /**
     * Get recommended period based on pricing
     */
    private function getRecommendedPeriod(array $prices): string
    {
        $bestValue = 'monthly';
        $lowestMonthlyRate = $prices['monthly']['monthly_equivalent'];
        
        foreach ($prices as $period => $pricing) {
            if ($pricing['monthly_equivalent'] < $lowestMonthlyRate) {
                $lowestMonthlyRate = $pricing['monthly_equivalent'];
                $bestValue = $period;
            }
        }
        
        return $bestValue;
    }

    /**
     * Calculate relevance score for unit recommendations
     */
    private function calculateRelevanceScore(Unit $unit, array $criteria): int
    {
        $score = 0;
        
        // Budget compatibility (40 points max)
        if (isset($criteria['max_budget'])) {
            $budgetRatio = $unit->rent_price / $criteria['max_budget'];
            if ($budgetRatio <= 0.8) $score += 40;
            elseif ($budgetRatio <= 0.9) $score += 30;
            elseif ($budgetRatio <= 1.0) $score += 20;
        }
        
        // Room count match (25 points max)
        if (isset($criteria['min_rooms'])) {
            if ($unit->rooms_count >= $criteria['min_rooms']) {
                $score += 25;
            }
        }
        
        // Unit type match (20 points max)
        if (isset($criteria['unit_type']) && $unit->unit_type === $criteria['unit_type']) {
            $score += 20;
        }
        
        // Furnished preference (10 points max)
        if (isset($criteria['furnished']) && $unit->furnished === $criteria['furnished']) {
            $score += 10;
        }
        
        // Additional features (5 points max)
        if ($unit->has_balcony) $score += 1;
        if ($unit->has_parking) $score += 2;
        if ($unit->has_storage) $score += 1;
        if ($unit->has_maid_room) $score += 1;
        
        return $score;
    }

    /**
     * Get unit type distribution
     */
    private function getUnitTypeDistribution(Collection $units): array
    {
        return $units->groupBy('unit_type')
            ->map(fn($group) => $group->count())
            ->toArray();
    }

    /**
     * Get floor distribution
     */
    private function getFloorDistribution(Collection $units): array
    {
        return $units->groupBy('floor_number')
            ->map(fn($group) => $group->count())
            ->sort()
            ->toArray();
    }
}
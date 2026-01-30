<?php

namespace App\Services;

use Exception;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UnitService
{
    /**
     * Check unit availability for date range.
     */
    public function checkUnitAvailability(int $unitId, array $dateRange = []): bool
    {
        $unit = Unit::with(['activeContract'])->findOrFail($unitId);

        if (! $unit->isAvailable()) {
            return false;
        }

        if (! empty($dateRange)) {
            $startDate = Carbon::parse($dateRange['start'] ?? now());

            // Check for overlapping contracts
            $hasOverlap = UnitContract::where('unit_id', $unitId)
                ->where('contract_status', 'active')
                ->where('start_date', '<=', $startDate)
                ->where('end_date', '>=', $startDate)
                ->exists();

            if ($hasOverlap) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assign tenant to unit via contract creation.
     */
    public function assignTenant(int $unitId, int $tenantId, array $contractData = []): array
    {
        return DB::transaction(function () use ($unitId, $tenantId, $contractData) {
            $unit = Unit::findOrFail($unitId);
            $tenant = User::findOrFail($tenantId);

            if (! $unit->isAvailable()) {
                throw new Exception('Unit is not available for assignment');
            }

            // Create contract for tenant assignment
            $contract = UnitContract::create([
                'unit_id' => $unitId,
                'tenant_id' => $tenantId,
                'property_id' => $unit->property_id,
                'monthly_rent' => $unit->rent_price,
                'start_date' => $contractData['start_date'] ?? now(),
                'duration_months' => $contractData['duration_months'] ?? 12,
                'contract_status' => 'active',
            ]);

            return [
                'success' => true,
                'unit' => $unit->fresh(['property', 'activeContract']),
                'tenant' => $tenant,
                'contract' => $contract,
                'message' => 'Tenant assigned successfully',
            ];
        });
    }

    /**
     * Release unit by terminating active contract.
     */
    public function releaseUnit(int $unitId, string $reason = ''): array
    {
        return DB::transaction(function () use ($unitId, $reason) {
            $unit = Unit::with(['activeContract.tenant'])->findOrFail($unitId);

            if (! $unit->isOccupied()) {
                throw new Exception('Unit is not currently occupied');
            }

            $previousTenant = $unit->current_tenant;
            $contract = $unit->activeContract;

            // Terminate the contract
            $contract->update([
                'contract_status' => 'terminated',
                'terminated_reason' => $reason,
                'terminated_at' => now(),
            ]);

            return [
                'success' => true,
                'unit' => $unit->fresh(['property']),
                'previous_tenant' => $previousTenant,
                'reason' => $reason,
                'message' => 'Unit released successfully',
            ];
        });
    }

    /**
     * Calculate unit pricing for different durations.
     */
    public function calculateUnitPricing(int $unitId, string $duration = 'monthly'): array
    {
        $unit = Unit::findOrFail($unitId);

        $basePrice = (float) $unit->rent_price;
        $discounts = $this->getPricingDiscounts($duration);

        $prices = [];

        foreach (['monthly', 'quarterly', 'semi_annual', 'annual'] as $period) {
            $periodPrice = $this->calculatePeriodPrice($basePrice, $period);
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
     * Calculate price for a given period.
     */
    private function calculatePeriodPrice(float $monthlyPrice, string $period): float
    {
        return match ($period) {
            'quarterly' => $monthlyPrice * 3,
            'semi_annual' => $monthlyPrice * 6,
            'annual' => $monthlyPrice * 12,
            default => $monthlyPrice,
        };
    }

    /**
     * Search units with advanced filters.
     */
    public function searchUnits(array $filters): Collection
    {
        $query = Unit::with(['property', 'activeContract.tenant', 'features', 'unitType']);

        if (isset($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        if (isset($filters['unit_type_id'])) {
            $query->where('unit_type_id', $filters['unit_type_id']);
        }

        // Availability filter via active contract
        if (isset($filters['availability'])) {
            if ($filters['availability'] === 'available') {
                $query->whereDoesntHave('activeContract');
            } elseif ($filters['availability'] === 'occupied') {
                $query->whereHas('activeContract');
            }
        }

        if (isset($filters['min_price'])) {
            $query->where('rent_price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('rent_price', '<=', $filters['max_price']);
        }

        if (isset($filters['min_area'])) {
            $query->where('area_sqm', '>=', $filters['min_area']);
        }
        if (isset($filters['max_area'])) {
            $query->where('area_sqm', '<=', $filters['max_area']);
        }

        if (isset($filters['rooms_count'])) {
            $query->where('rooms_count', $filters['rooms_count']);
        }

        if (isset($filters['min_floor'])) {
            $query->where('floor_number', '>=', $filters['min_floor']);
        }
        if (isset($filters['max_floor'])) {
            $query->where('floor_number', '<=', $filters['max_floor']);
        }

        return $query->get();
    }

    /**
     * Get unit recommendations based on criteria.
     */
    public function getUnitRecommendations(array $criteria): Collection
    {
        $query = Unit::with(['property', 'unitType'])
            ->whereDoesntHave('activeContract');

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

        if (isset($criteria['unit_type_id'])) {
            $query->where('unit_type_id', $criteria['unit_type_id']);
        }

        return $query->get()->map(function ($unit) use ($criteria) {
            $unit->relevance_score = $this->calculateRelevanceScore($unit, $criteria);

            return $unit;
        })->sortByDesc('relevance_score');
    }

    /**
     * Get units performance metrics.
     */
    public function getUnitsPerformanceMetrics(array $unitIds = []): array
    {
        $query = Unit::with(['property', 'activeContract']);

        if (! empty($unitIds)) {
            $query->whereIn('id', $unitIds);
        }

        $units = $query->get();

        $totalUnits = $units->count();
        $occupiedUnits = $units->filter(fn ($u) => $u->isOccupied())->count();
        $availableUnits = $totalUnits - $occupiedUnits;
        $totalRevenue = $units->filter(fn ($u) => $u->isOccupied())->sum('rent_price');
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
     * Get pricing discounts for different periods.
     */
    private function getPricingDiscounts(string $duration): array
    {
        return [
            'monthly' => 0,
            'quarterly' => 5,
            'semi_annual' => 8,
            'annual' => 12,
        ];
    }

    /**
     * Get monthly equivalent price.
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
     * Get recommended period based on pricing.
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
     * Calculate relevance score for unit recommendations.
     */
    private function calculateRelevanceScore(Unit $unit, array $criteria): int
    {
        $score = 0;

        // Budget compatibility (40 points max)
        if (isset($criteria['max_budget']) && $criteria['max_budget'] > 0) {
            $budgetRatio = (float) $unit->rent_price / $criteria['max_budget'];
            if ($budgetRatio <= 0.8) {
                $score += 40;
            } elseif ($budgetRatio <= 0.9) {
                $score += 30;
            } elseif ($budgetRatio <= 1.0) {
                $score += 20;
            }
        }

        // Room count match (25 points max)
        if (isset($criteria['min_rooms']) && $unit->rooms_count >= $criteria['min_rooms']) {
            $score += 25;
        }

        // Unit type match (20 points max)
        if (isset($criteria['unit_type_id']) && $unit->unit_type_id === $criteria['unit_type_id']) {
            $score += 20;
        }

        // Balconies bonus (5 points max)
        if ($unit->balconies_count > 0) {
            $score += min($unit->balconies_count, 5);
        }

        return $score;
    }

    /**
     * Get unit type distribution.
     */
    private function getUnitTypeDistribution(Collection $units): array
    {
        return $units->groupBy('unit_type_id')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }

    /**
     * Get floor distribution.
     */
    private function getFloorDistribution(Collection $units): array
    {
        return $units->groupBy('floor_number')
            ->map(fn ($group) => $group->count())
            ->sort()
            ->toArray();
    }
}

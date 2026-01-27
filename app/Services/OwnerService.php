<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\Owner;
use App\Models\Property;
use App\Models\SupplyPayment;
use App\Models\Unit;
use App\Models\UnitContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class OwnerService
{
    /**
     * Calculate total supplied rental income.
     */
    public function calculateTotalRentalIncome(Owner $owner): float
    {
        return SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->sum('net_amount');
    }

    /**
     * Calculate total deducted commissions.
     */
    public function calculateTotalCommissions(Owner $owner): float
    {
        return SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->sum('commission_amount');
    }

    /**
     * Calculate total deducted expenses.
     */
    public function calculateTotalDeductions(Owner $owner): float
    {
        return SupplyPayment::where('owner_id', $owner->id)
            ->collected()
            ->sum('maintenance_deduction');
    }

    /**
     * Get active properties count.
     */
    public function getActivePropertiesCount(Owner $owner): int
    {
        return Property::where('owner_id', $owner->id)
            ->whereHas('propertyStatus', fn ($q) => $q->where('slug', 'available')->orWhere('is_active', true))
            ->count();
    }

    /**
     * Get vacant properties.
     */
    public function getVacantProperties(Owner $owner): Collection
    {
        return Property::where('owner_id', $owner->id)
            ->whereHas('propertyStatus', fn ($q) => $q->where('slug', 'available'))
            ->get();
    }

    /**
     * Get vacant units.
     */
    public function getVacantUnits(Owner $owner): Collection
    {
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        return Unit::whereIn('property_id', $propertyIds)
            ->whereDoesntHave('contracts', function ($query) {
                $query->active();
            })
            ->with('property')
            ->get();
    }

    /**
     * Get occupied units.
     */
    public function getOccupiedUnits(Owner $owner): Collection
    {
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        return Unit::whereIn('property_id', $propertyIds)
            ->whereHas('contracts', function ($query) {
                $query->active();
            })
            ->with(['property', 'contracts' => function ($q) {
                $q->active()->with('tenant');
            }])
            ->get();
    }

    /**
     * Calculate occupancy rate.
     */
    public function calculateOccupancyRate(Owner $owner): float
    {
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');
        $totalUnits = Unit::whereIn('property_id', $propertyIds)->count();

        if ($totalUnits === 0) {
            return 0;
        }

        $occupiedUnits = Unit::whereIn('property_id', $propertyIds)
            ->whereHas('contracts', function ($query) {
                $query->active();
            })
            ->count();

        return round(($occupiedUnits / $totalUnits) * 100, 2);
    }

    /**
     * Get supply payments.
     */
    public function getSupplyPayments(Owner $owner, ?string $status = null): Collection
    {
        $query = SupplyPayment::where('owner_id', $owner->id)
            ->with('propertyContract.property')
            ->orderBy('due_date', 'desc');

        if ($status) {
            switch ($status) {
                case 'pending':
                    $query->pending();
                    break;
                case 'worth_collecting':
                    $query->worthCollecting();
                    break;
                case 'collected':
                    $query->collected();
                    break;
            }
        }

        return $query->get();
    }

    /**
     * Get pending supply payments.
     */
    public function getPendingSupplyPayments(Owner $owner): Collection
    {
        return SupplyPayment::where('owner_id', $owner->id)
            ->worthCollecting()
            ->with('propertyContract.property')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get comprehensive financial summary.
     */
    public function getFinancialSummary(Owner $owner, ?string $period = null): array
    {
        $supplyPaymentsQuery = SupplyPayment::where('owner_id', $owner->id);

        if ($period) {
            switch ($period) {
                case 'this_month':
                    $supplyPaymentsQuery->where('month_year', Carbon::now()->format('Y-m'));
                    break;
                case 'this_year':
                    $supplyPaymentsQuery->whereYear('due_date', Carbon::now()->year);
                    break;
                case 'last_month':
                    $supplyPaymentsQuery->where('month_year', Carbon::now()->subMonth()->format('Y-m'));
                    break;
                case 'last_year':
                    $supplyPaymentsQuery->whereYear('due_date', Carbon::now()->subYear()->year);
                    break;
            }
        }

        $supplyPayments = $supplyPaymentsQuery->get();
        $collectedPayments = $supplyPayments->filter(fn ($p) => $p->paid_date !== null);
        $pendingPayments = $supplyPayments->filter(fn ($p) => $p->paid_date === null);

        return [
            'total_gross_amount' => $collectedPayments->sum('gross_amount'),
            'total_net_amount' => $collectedPayments->sum('net_amount'),
            'total_commissions' => $collectedPayments->sum('commission_amount'),
            'total_deductions' => $collectedPayments->sum('maintenance_deduction'),
            'pending_amount' => $pendingPayments->sum('net_amount'),
            'payments_count' => [
                'total' => $supplyPayments->count(),
                'collected' => $collectedPayments->count(),
                'pending' => $pendingPayments->count(),
            ],
        ];
    }

    /**
     * Get properties summary.
     */
    public function getPropertiesSummary(Owner $owner): array
    {
        $properties = Property::where('owner_id', $owner->id)
            ->withCount('units')
            ->get();

        $propertyIds = $properties->pluck('id');
        $totalUnits = Unit::whereIn('property_id', $propertyIds)->count();

        $occupiedUnits = Unit::whereIn('property_id', $propertyIds)
            ->whereHas('contracts', function ($query) {
                $query->active();
            })
            ->count();

        $monthlyRent = UnitContract::whereIn('property_id', $propertyIds)
            ->active()
            ->sum('monthly_rent');

        return [
            'total_properties' => $properties->count(),
            'active_properties' => $properties->filter(fn ($p) => $p->propertyStatus?->is_active)->count(),
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $totalUnits - $occupiedUnits,
            'occupancy_rate' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0,
            'expected_monthly_rent' => $monthlyRent,
            'properties' => $properties->map(function ($property) {
                return [
                    'id' => $property->id,
                    'name' => $property->name,
                    'status' => $property->propertyStatus?->name_ar ?? 'غير محدد',
                    'units_count' => $property->units_count,
                ];
            }),
        ];
    }

    /**
     * Get collection report for owner.
     */
    public function getCollectionReport(Owner $owner, string $monthYear): array
    {
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $collectionPayments = CollectionPayment::whereIn('property_id', $propertyIds)
            ->where('month_year', $monthYear)
            ->with(['unit', 'property', 'tenant'])
            ->get();

        $collected = $collectionPayments->filter(fn ($p) => $p->collection_date !== null);
        $pending = $collectionPayments->filter(fn ($p) => $p->collection_date === null);

        return [
            'month_year' => $monthYear,
            'total_expected' => $collectionPayments->sum('total_amount'),
            'total_collected' => $collected->sum('total_amount'),
            'total_pending' => $pending->sum('total_amount'),
            'collection_rate' => $collectionPayments->count() > 0
                ? round(($collected->count() / $collectionPayments->count()) * 100, 2)
                : 0,
            'payments' => [
                'collected' => $collected->values(),
                'pending' => $pending->values(),
            ],
        ];
    }

    /**
     * Get quick statistics.
     */
    public function getQuickStats(Owner $owner): array
    {
        return [
            'total_rental_income' => $this->calculateTotalRentalIncome($owner),
            'active_properties' => $this->getActivePropertiesCount($owner),
            'occupancy_rate' => $this->calculateOccupancyRate($owner),
            'pending_supply_payments' => SupplyPayment::where('owner_id', $owner->id)
                ->worthCollecting()
                ->count(),
            'pending_supply_amount' => SupplyPayment::where('owner_id', $owner->id)
                ->worthCollecting()
                ->sum('net_amount'),
        ];
    }

    /**
     * Search owners by criteria.
     */
    public function searchOwners(array $criteria): Collection
    {
        $query = Owner::query();

        if (isset($criteria['name'])) {
            $query->where('name', 'like', '%'.$criteria['name'].'%');
        }

        if (isset($criteria['phone'])) {
            $query->where('phone', 'like', '%'.$criteria['phone'].'%');
        }

        if (isset($criteria['has_active_properties']) && $criteria['has_active_properties']) {
            $query->whereHas('properties', function ($q) {
                $q->whereHas('propertyStatus', fn ($sq) => $sq->where('is_active', true));
            });
        }

        if (isset($criteria['min_properties'])) {
            $query->has('properties', '>=', $criteria['min_properties']);
        }

        return $query->with('properties')->get();
    }
}

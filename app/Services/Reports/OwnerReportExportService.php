<?php

namespace App\Services\Reports;

use App\Models\Owner;
use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OwnerReportExportService
{
    public function generateReportData(int $ownerId, string $dateFrom, string $dateTo, string $reportType = 'summary'): array
    {
        $owner = Owner::with(['properties', 'properties.units', 'properties.location'])->find($ownerId);
        
        if (!$owner) {
            throw new \Exception('المالك غير موجود');
        }

        $dateFromCarbon = Carbon::parse($dateFrom);
        $dateToCarbon = Carbon::parse($dateTo);

        // حساب البيانات الأساسية
        $basicData = $this->calculateBasicData($owner, $dateFromCarbon, $dateToCarbon);
        
        // حساب بيانات العقارات
        $propertiesData = $this->calculatePropertiesData($owner, $dateFromCarbon, $dateToCarbon);
        
        // حساب البيانات الشهرية
        $monthlyData = $this->calculateMonthlyData($owner, $dateFromCarbon, $dateToCarbon);

        return [
            'owner' => $owner,
            'date_from' => $dateFromCarbon,
            'date_to' => $dateToCarbon,
            'report_type' => $reportType,
            'basic_data' => $basicData,
            'properties_data' => $propertiesData,
            'monthly_data' => $monthlyData,
            'generated_at' => now(),
        ];
    }

    private function calculateBasicData(Owner $owner, Carbon $dateFrom, Carbon $dateTo): array
    {
        // حساب إجمالي التحصيل
        $totalCollection = CollectionPayment::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('paid_date', [$dateFrom, $dateTo])
            ->whereHas('paymentStatus', function ($query) {
                $query->where('is_paid_status', true);
            })
            ->sum('total_amount');

        // حساب النسبة الإدارية (10%)
        $managementPercentage = 10;
        $managementFee = $totalCollection * ($managementPercentage / 100);

        // حساب تكاليف الصيانة
        $maintenanceCosts = PropertyRepair::whereHas('property', function ($query) use ($owner) {
                $query->where('owner_id', $owner->id);
            })
            ->whereBetween('completion_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['completed'])
            ->sum('actual_cost');

        // حساب صافي الدخل
        $netIncome = $totalCollection - $managementFee - $maintenanceCosts;

        // حساب الإحصائيات العامة
        $propertiesCount = $owner->properties()->count();
        $totalUnits = $owner->properties()->withCount('units')->get()->sum('units_count');
        $occupiedUnits = $this->calculateOccupiedUnits($owner);
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0;

        // حساب متوسطات
        $monthsDiff = $dateFrom->diffInMonths($dateTo) + 1;
        $averageMonthlyIncome = $monthsDiff > 0 ? $netIncome / $monthsDiff : 0;
        $averageMonthlyCollection = $monthsDiff > 0 ? $totalCollection / $monthsDiff : 0;

        return [
            'total_collection' => $totalCollection,
            'management_fee' => $managementFee,
            'management_percentage' => $managementPercentage,
            'maintenance_costs' => $maintenanceCosts,
            'net_income' => $netIncome,
            'properties_count' => $propertiesCount,
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'vacant_units' => $totalUnits - $occupiedUnits,
            'occupancy_rate' => $occupancyRate,
            'average_monthly_income' => $averageMonthlyIncome,
            'average_monthly_collection' => $averageMonthlyCollection,
            'period_months' => $monthsDiff,
        ];
    }

    private function calculatePropertiesData(Owner $owner, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        return $owner->properties->map(function (Property $property) use ($dateFrom, $dateTo) {
            // حساب التحصيل لهذا العقار
            $propertyCollection = CollectionPayment::where('property_id', $property->id)
                ->whereBetween('paid_date', [$dateFrom, $dateTo])
                ->whereHas('paymentStatus', function ($query) {
                    $query->where('is_paid_status', true);
                })
                ->sum('total_amount');

            // حساب تكاليف الصيانة لهذا العقار
            $propertyMaintenance = PropertyRepair::where('property_id', $property->id)
                ->whereBetween('completion_date', [$dateFrom, $dateTo])
                ->whereIn('status', ['completed'])
                ->sum('actual_cost');

            // حساب الإحصائيات
            $totalUnits = $property->units()->count();
            $occupiedUnits = $property->units()->whereNotNull('current_tenant_id')->count();
            $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 2) : 0;
            $monthlyRevenue = $property->units()->whereNotNull('current_tenant_id')->sum('rent_price');

            // حساب النسبة الإدارية وصافي الدخل
            $managementFee = $propertyCollection * 0.1;
            $netIncome = $propertyCollection - $managementFee - $propertyMaintenance;

            return [
                'id' => $property->id,
                'name' => $property->name,
                'location' => $property->location->name_ar ?? 'غير محدد',
                'address' => $property->address,
                'total_units' => $totalUnits,
                'occupied_units' => $occupiedUnits,
                'vacant_units' => $totalUnits - $occupiedUnits,
                'occupancy_rate' => $occupancyRate,
                'monthly_revenue' => $monthlyRevenue,
                'total_collection' => $propertyCollection,
                'maintenance_costs' => $propertyMaintenance,
                'management_fee' => $managementFee,
                'net_income' => $netIncome,
                'area_sqm' => $property->area_sqm,
                'build_year' => $property->build_year,
            ];
        });
    }

    private function calculateMonthlyData(Owner $owner, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $monthlyData = collect();
        $period = $dateFrom->copy();

        while ($period->lte($dateTo)) {
            // حساب التحصيل الشهري
            $monthlyCollection = CollectionPayment::whereHas('property', function ($query) use ($owner) {
                    $query->where('owner_id', $owner->id);
                })
                ->whereYear('paid_date', $period->year)
                ->whereMonth('paid_date', $period->month)
                ->whereHas('paymentStatus', function ($query) {
                    $query->where('is_paid_status', true);
                })
                ->sum('total_amount');

            // حساب تكاليف الصيانة الشهرية
            $monthlyMaintenance = PropertyRepair::whereHas('property', function ($query) use ($owner) {
                    $query->where('owner_id', $owner->id);
                })
                ->whereYear('completion_date', $period->year)
                ->whereMonth('completion_date', $period->month)
                ->whereIn('status', ['completed'])
                ->sum('actual_cost');

            // حساب النسبة الإدارية وصافي الدخل
            $managementFee = $monthlyCollection * 0.1;
            $netIncome = $monthlyCollection - $managementFee - $monthlyMaintenance;

            $monthlyData->push([
                'year' => $period->year,
                'month' => $period->month,
                'month_name' => $period->locale('ar')->format('F Y'),
                'collection' => $monthlyCollection,
                'maintenance' => $monthlyMaintenance,
                'management_fee' => $managementFee,
                'net_income' => $netIncome,
            ]);

            $period->addMonth();
        }

        return $monthlyData;
    }

    private function calculateOccupiedUnits(Owner $owner): int
    {
        return $owner->properties()
            ->withCount(['units' => function ($query) {
                $query->whereNotNull('current_tenant_id');
            }])
            ->get()
            ->sum('units_count');
    }

    public function generatePdfFileName(int $ownerId, string $dateFrom, string $dateTo): string
    {
        $owner = Owner::find($ownerId);
        $ownerName = $owner ? str_replace(' ', '_', $owner->name) : 'مالك_غير_محدد';
        $fromDate = Carbon::parse($dateFrom)->format('Y-m-d');
        $toDate = Carbon::parse($dateTo)->format('Y-m-d');
        
        return "تقرير_المالك_{$ownerName}_{$fromDate}_الى_{$toDate}.pdf";
    }

    public function generateExcelFileName(int $ownerId, string $dateFrom, string $dateTo): string
    {
        $owner = Owner::find($ownerId);
        $ownerName = $owner ? str_replace(' ', '_', $owner->name) : 'مالك_غير_محدد';
        $fromDate = Carbon::parse($dateFrom)->format('Y-m-d');
        $toDate = Carbon::parse($dateTo)->format('Y-m-d');
        
        return "تقرير_المالك_{$ownerName}_{$fromDate}_الى_{$toDate}.xlsx";
    }
}
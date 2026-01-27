<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\Property;
use App\Models\PropertyRepair;
use App\Models\SupplyPayment;
use App\Models\UnitContract;

class PropertyReportService
{
    /**
     * Get view data for property report
     */
    public function getReportData(Property $property): array
    {
        $collectionTotal = CollectionPayment::where('property_id', $property->id)
            ->collectedPayments()
            ->sum('total_amount');

        $supplyTotal = SupplyPayment::whereHas('propertyContract', function ($query) use ($property) {
            $query->where('property_id', $property->id);
        })
            ->collected()
            ->sum('net_amount');

        $nextPayment = CollectionPayment::where('property_id', $property->id)
            ->dueForCollection()
            ->orderBy('due_date_start')
            ->first();

        return [
            'collectionTotal' => $collectionTotal,
            'supplyTotal' => $supplyTotal,
            'generalReport' => $this->getGeneralReport($property, $nextPayment),
            'operationsReport' => $this->getOperationsReport($property),
            'operationsTotal' => $this->getOperationsTotal($property),
            'detailedReport' => $this->getDetailedReport($property),
        ];
    }

    /**
     * Get general report data
     */
    private function getGeneralReport(Property $property, ?CollectionPayment $nextPayment): array
    {
        return [
            'property_name' => $property->name,
            'owner_name' => $property->owner?->name ?? '-',
            'units_count' => $property->units()->count(),
            'property_status' => $property->status ?? 'متاح',
            'collected_rent' => CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->whereMonth('collection_date', now()->month)
                ->sum('total_amount'),
            'next_collection' => $nextPayment?->total_amount ?? 0,
            'next_collection_date' => $nextPayment?->due_date_start ?? null,
        ];
    }

    /**
     * Get operations report data
     */
    private function getOperationsReport(Property $property): array
    {
        $operationsReport = [];

        $collectionOperations = CollectionPayment::where('property_id', $property->id)
            ->collectedPayments()
            ->get();

        foreach ($collectionOperations as $collection) {
            $operationsReport[] = [
                'name' => 'تحصيل إيجار - وحدة '.($collection->unitContract->unit->name ?? ''),
                'type' => 'تحصيل',
                'amount' => $collection->total_amount,
            ];
        }

        $maintenanceOperations = PropertyRepair::where('property_id', $property->id)
            ->where('status', 'completed')
            ->get();

        foreach ($maintenanceOperations as $maintenance) {
            $operationsReport[] = [
                'name' => 'صيانة - '.($maintenance->description ?? 'عملية صيانة'),
                'type' => 'مصروفات',
                'amount' => $maintenance->cost ?? 0,
            ];
        }

        return $operationsReport;
    }

    /**
     * Get operations total
     */
    private function getOperationsTotal(Property $property): float
    {
        return collect($this->getOperationsReport($property))->sum('amount');
    }

    /**
     * Get detailed report data
     */
    private function getDetailedReport(Property $property): array
    {
        $contracts = UnitContract::where('property_id', $property->id)
            ->where('contract_status', 'active')
            ->with(['unit', 'tenant'])
            ->get();

        $detailedReport = [];
        $totalAmount = 0;
        $totalAdminFee = 0;
        $totalMaintenance = 0;
        $totalNet = 0;

        foreach ($contracts as $contract) {
            $lastPayment = CollectionPayment::where('unit_contract_id', $contract->id)->latest()->first();

            if ($lastPayment) {
                $amount = $lastPayment->total_amount;
                $adminFee = $amount * 0.05;
                $maintenance = 0;
                $net = $amount - $adminFee - $maintenance;

                $detailedReport[] = [
                    'unit_number' => $contract->unit?->name ?? '-',
                    'tenant_name' => $contract->tenant?->name ?? '-',
                    'total_payments' => CollectionPayment::where('unit_contract_id', $contract->id)->count(),
                    'payment_date' => $lastPayment->collection_date,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'maintenance' => $maintenance,
                    'net' => $net,
                ];

                $totalAmount += $amount;
                $totalAdminFee += $adminFee;
                $totalMaintenance += $maintenance;
                $totalNet += $net;
            }
        }

        return [
            'data' => $detailedReport,
            'totals' => [
                'amount' => $totalAmount,
                'admin_fee' => $totalAdminFee,
                'maintenance' => $totalMaintenance,
                'net' => $totalNet,
            ],
        ];
    }
}

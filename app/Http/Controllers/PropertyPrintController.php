<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\UnitContract;
use App\Models\PropertyRepair;

class PropertyPrintController extends Controller
{
    public function print(Property $property)
    {
        // حساب الإجماليات
        $collectionTotal = CollectionPayment::where('property_id', $property->id)
            ->where('collection_status', 'collected')
            ->sum('total_amount');
            
        $supplyTotal = SupplyPayment::whereHas('propertyContract', function ($query) use ($property) {
            $query->where('property_id', $property->id);
        })
        ->where('supply_status', 'collected')
        ->sum('net_amount');
        
        // بيانات الجدول الأول
        $nextPayment = CollectionPayment::where('property_id', $property->id)
            ->where('collection_status', 'due')
            ->orderBy('due_date_start')
            ->first();
            
        $generalReport = [
            'property_name' => $property->name,
            'owner_name' => $property->owner?->name ?? '-',
            'units_count' => $property->units()->count(),
            'property_status' => $property->status ?? 'متاح',
            'collected_rent' => CollectionPayment::where('property_id', $property->id)
                ->where('collection_status', 'collected')
                ->whereMonth('collection_date', now()->month)
                ->sum('total_amount'),
            'next_collection' => $nextPayment?->total_amount ?? 0,
            'next_collection_date' => $nextPayment?->due_date_start ?? null,
        ];
        
        // بيانات الجدول الثاني
        $operationsReport = [];
        
        // إضافة عمليات التحصيل
        $collectionOperations = CollectionPayment::where('property_id', $property->id)
            ->where('collection_status', 'collected')
            ->get();
            
        foreach ($collectionOperations as $collection) {
            $operationsReport[] = [
                'name' => 'تحصيل إيجار - وحدة ' . ($collection->unitContract->unit->name ?? ''),
                'type' => 'تحصيل',
                'amount' => $collection->total_amount,
            ];
        }
        
        // إضافة عمليات الصيانة
        $maintenanceOperations = PropertyRepair::where('property_id', $property->id)
            ->where('status', 'completed')
            ->get();
            
        foreach ($maintenanceOperations as $maintenance) {
            $operationsReport[] = [
                'name' => 'صيانة - ' . ($maintenance->description ?? 'عملية صيانة'),
                'type' => 'مصروفات',
                'amount' => $maintenance->cost ?? 0,
            ];
        }
        
        // حساب الإجمالي
        $operationsTotal = collect($operationsReport)->sum('amount');
        
        // بيانات الجدول الثالث
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
        
        return view('print.property-report', [
            'record' => $property,
            'collectionTotal' => $collectionTotal,
            'supplyTotal' => $supplyTotal,
            'generalReport' => $generalReport,
            'operationsReport' => $operationsReport,
            'operationsTotal' => $operationsTotal,
            'detailedReport' => [
                'data' => $detailedReport,
                'totals' => [
                    'amount' => $totalAmount,
                    'admin_fee' => $totalAdminFee,
                    'maintenance' => $totalMaintenance,
                    'net' => $totalNet,
                ],
            ],
        ]);
    }
}
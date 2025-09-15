<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Resources\Pages\Page;
use App\Models\CollectionPayment;
use App\Models\SupplyPayment;
use App\Models\UnitContract;
use App\Models\PropertyRepair;
use App\Models\Expense;

class PrintProperty extends Page
{
    protected static string $resource = PropertyResource::class;
    
    protected string $view = 'filament.resources.property-resource.pages.print-property';
    
    protected static ?string $title = 'طباعة تقرير العقار';
    
    public function mount($record)
    {
        $this->record = $this->getResource()::resolveRecordRouteBinding($record);
    }
    
    protected function getViewData(): array
    {
        // حساب الإجماليات
        $collectionTotal = CollectionPayment::where('property_id', $this->record->id)
            ->collectedPayments()
            ->sum('total_amount');
            
        $supplyTotal = SupplyPayment::whereHas('propertyContract', function ($query) {
            $query->where('property_id', $this->record->id);
        })
        ->collected()
        ->sum('net_amount');
        
        // بيانات الجدول الأول
        $nextPayment = CollectionPayment::where('property_id', $this->record->id)
            ->dueForCollection()
            ->orderBy('due_date_start')
            ->first();
            
        $generalReport = [
            'property_name' => $this->record->name,
            'owner_name' => $this->record->owner?->name ?? '-',
            'units_count' => $this->record->units()->count(),
            'property_status' => $this->record->status ?? 'متاح',
            'collected_rent' => CollectionPayment::where('property_id', $this->record->id)
                ->collectedPayments()
                ->whereMonth('collection_date', now()->month)
                ->sum('total_amount'),
            'next_collection' => $nextPayment?->total_amount ?? 0,
            'next_collection_date' => $nextPayment?->due_date_start ?? null,
        ];
        
        // بيانات الجدول الثاني
        $operationsReport = [];
        
        // إضافة عمليات التحصيل
        $collectionOperations = CollectionPayment::where('property_id', $this->record->id)
            ->collectedPayments()
            ->get();
            
        foreach ($collectionOperations as $collection) {
            $operationsReport[] = [
                'name' => 'تحصيل إيجار - وحدة ' . ($collection->unitContract->unit->name ?? ''),
                'type' => 'تحصيل',
                'amount' => $collection->total_amount,
            ];
        }
        
        // إضافة عمليات الصيانة
        $maintenanceOperations = PropertyRepair::where('property_id', $this->record->id)
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
        $contracts = UnitContract::where('property_id', $this->record->id)
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
            'record' => $this->record,
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
        ];
    }
}
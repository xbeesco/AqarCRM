<?php

namespace App\Filament\Resources\OwnerResource\Pages;

use App\Filament\Resources\OwnerResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Models\Property;
use App\Models\CollectionPayment;
use App\Models\PropertyRepair;

class ViewOwner extends ViewRecord
{
    protected static string $resource = OwnerResource::class;
    
    protected static ?string $title = 'عرض المالك';
    
    protected string $view = 'filament.resources.owner-resource.pages.view-owner';
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('الملاك')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(OwnerResource::getUrl('index')),
            Action::make('print')
                ->label('طباعة التقرير')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->modalHeading('طباعة تقرير المالك')
                ->modalContent(function () {
                    $data = $this->getViewData();
                    return view('filament.resources.owner-resource.pages.print-owner', $data);
                })
                ->modalWidth('7xl')
                ->modalFooterActions([
                    Action::make('printReport')
                        ->label('طباعة التقرير')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->extraAttributes([
                            'onclick' => "
                                var printContent = document.querySelector('.print-content').innerHTML;
                                var originalContent = document.body.innerHTML;
                                document.body.innerHTML = printContent;
                                window.print();
                                document.body.innerHTML = originalContent;
                                window.location.reload();
                                return false;
                            ",
                        ]),
                    Action::make('close')
                        ->label('إلغاء')
                        ->color('gray')
                        ->close(),
                ]),
            EditAction::make()->label('تعديل'),
        ];
    }
    
    protected function getViewData(): array
    {
        $owner = $this->record;
        
        // جلب عقارات المالك مع العلاقات باستخدام eager loading
        $properties = Property::where('owner_id', $owner->id)
            ->with([
                'location',
                'propertyType',
                'propertyStatus',
                'units' => function($query) {
                    $query->with([
                        'unitType',
                        'activeContract' => function($q) {
                            $q->with(['tenant', 'payments']);
                        }
                    ]);
                }
            ])
            ->get();

        // حساب الإحصائيات العامة
        $totalProperties = $properties->count();
        $totalUnits = $properties->sum(function($property) {
            return $property->units->count();
        });
        $occupiedUnits = $properties->sum(function($property) {
            return $property->units->filter(function($unit) {
                return $unit->activeContract !== null;
            })->count();
        });
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
        
        // بيانات الجدول الأول - تقرير العقارات
        $propertiesReport = [];
        $totalIncome = 0;
        $totalAdminFee = 0;
        $totalPaid = 0;
        $totalOverdue = 0;
        
        foreach ($properties as $property) {
            // حساب إجمالي الدخل من التحصيل المحصل
            $propertyIncome = CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->sum('total_amount');
                
            // حساب المدفوع والمتأخر
            $propertyPaid = CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->sum('total_amount');
                
            $propertyOverdue = CollectionPayment::where('property_id', $property->id)
                ->overduePayments()
                ->sum('total_amount');
            
            // نسبة الإدارة من عقد العقار
            $propertyContract = \App\Models\PropertyContract::where('property_id', $property->id)
                ->where('contract_status', 'active')
                ->first();
            
            $adminPercentage = $propertyContract ? $propertyContract->commission_rate : 0;
            $adminFee = $propertyIncome * ($adminPercentage / 100);
            
            $propertiesReport[] = [
                'property_name' => $property->name,
                'location' => $property->location?->name ?? 'غير محدد',
                'is_residential' => $property->propertyType?->slug === 'residential' ? 'نعم' : 'لا',
                'is_commercial' => $property->propertyType?->slug === 'commercial' ? 'نعم' : 'لا',
                'property_category' => $property->propertyType?->name ?? '-',
                'collection_payments' => CollectionPayment::where('property_id', $property->id)->count(),
                'total_income' => $propertyIncome,
                'admin_percentage' => $adminPercentage,
                'admin_fee' => $adminFee,
                'paid_amount' => $propertyPaid,
                'overdue_amount' => $propertyOverdue,
                'units_count' => $property->units->count(),
                'occupied_units' => $property->units->filter(function($unit) {
                    return $unit->activeContract !== null;
                })->count(),
            ];
            
            $totalIncome += $propertyIncome;
            $totalAdminFee += $adminFee;
            $totalPaid += $propertyPaid;
            $totalOverdue += $propertyOverdue;
        }
        
        
        // بيانات الجدول الثالث - معلومات المستأجرين والعقود النشطة
        $tenantsReport = [];
        foreach ($properties as $property) {
            foreach ($property->units as $unit) {
                if ($unit->activeContract) {
                    $contract = $unit->activeContract;
                    $remainingDays = $contract->getRemainingDays();
                    
                    // حساب المدفوع والمتأخر للعقد
                    $contractPaid = $contract->payments()
                        ->collectedPayments()
                        ->sum('total_amount');
                    $contractOverdue = $contract->payments()
                        ->overduePayments()
                        ->sum('total_amount');
                    
                    $tenantsReport[] = [
                        'property_name' => $property->name,
                        'unit_name' => $unit->name,
                        'tenant_name' => $contract->tenant->name,
                        'tenant_phone' => $contract->tenant->phone,
                        'monthly_rent' => $contract->monthly_rent,
                        'contract_start' => $contract->start_date,
                        'contract_end' => $contract->end_date,
                        'remaining_days' => $remainingDays,
                        'contract_status' => $contract->getStatusLabel(),
                        'contract_status_color' => $contract->getStatusColor(),
                        'paid_amount' => $contractPaid,
                        'overdue_amount' => $contractOverdue,
                    ];
                }
            }
        }
        
            
        // حساب الإيرادات الشهرية والسنوية
        $monthlyRevenue = $properties->sum(function($property) {
            return $property->units->sum(function($unit) {
                return $unit->activeContract ? $unit->activeContract->monthly_rent : 0;
            });
        });
        $annualRevenue = $monthlyRevenue * 12;
        
        // بيانات الجدول الثاني - تقرير مالك العقار التفصيلي
        $ownerDetailedReport = [];
        $detailedTotals = [
            'payment_amount' => 0,
            'admin_fee' => 0,
            'maintenance_special' => 0,
            'government_obligations' => 0,
            'general_maintenance' => 0,
            'general_obligations' => 0,
            'net_income' => 0,
            'grand_total' => 0,
        ];
        
        foreach ($properties as $property) {
            // حساب مصاريف الصيانة الخاصة بالعقار
            $maintenanceExpenses = PropertyRepair::where('property_id', $property->id)
                ->whereYear('maintenance_date', date('Y'))
                ->sum('total_cost');
            
            // حساب الإيرادات والمصاريف للعقار
            $propertyIncome = CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->sum('total_amount');
            
            // نسبة الإدارة من عقد العقار
            $propertyContract = \App\Models\PropertyContract::where('property_id', $property->id)
                ->where('contract_status', 'active')
                ->first();
            
            $adminPercentage = $propertyContract ? $propertyContract->commission_rate : 0;
            $adminFee = $propertyIncome * ($adminPercentage / 100);
            
            // حساب صافي المبلغ للمالك
            $netIncome = $propertyIncome - $adminFee - $maintenanceExpenses;
            
            // معلومات الوحدات
            $unitsCount = $property->units->count();
            $unitTypes = $property->units->pluck('unitType.name')->unique()->implode(', ');
            
            // تواريخ الدفع من أول وآخر دفعة
            $firstPayment = CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->orderBy('due_date_start', 'asc')
                ->first();
            
            $lastPayment = CollectionPayment::where('property_id', $property->id)
                ->collectedPayments()
                ->orderBy('due_date_end', 'desc')
                ->first();
            
            $ownerDetailedReport[] = [
                'property_name' => $property->name,
                'units_count' => $unitsCount,
                'unit_type' => $unitTypes ?: '-',
                'payment_date_from' => $firstPayment?->due_date_start ?? '-',
                'payment_date_to' => $lastPayment?->due_date_end ?? '-',
                'payment_amount' => $propertyIncome,
                'admin_fee' => $adminFee,
                'maintenance_special' => $maintenanceExpenses,
                'government_obligations' => 0, // يمكن إضافة الحساب الفعلي لاحقاً
                'net_income' => $netIncome,
            ];
            
            // تحديث الإجماليات
            $detailedTotals['payment_amount'] += $propertyIncome;
            $detailedTotals['admin_fee'] += $adminFee;
            $detailedTotals['maintenance_special'] += $maintenanceExpenses;
            $detailedTotals['net_income'] += $netIncome;
        }
        
        // حساب الإجمالي الكلي
        $detailedTotals['grand_total'] = $detailedTotals['net_income'] - 
            $detailedTotals['general_maintenance'] - 
            $detailedTotals['general_obligations'];
        
        return [
            'owner' => $owner,
            'summary' => [
                'total_properties' => $totalProperties,
                'total_units' => $totalUnits,
                'occupied_units' => $occupiedUnits,
                'vacant_units' => $vacantUnits,
                'occupancy_rate' => $occupancyRate,
                'monthly_revenue' => $monthlyRevenue,
                'annual_revenue' => $annualRevenue,
                'total_paid' => $totalPaid,
                'total_overdue' => $totalOverdue,
            ],
            'propertiesReport' => $propertiesReport,
            'propertiesTotal' => [
                'total_income' => $totalIncome,
                'total_admin_fee' => $totalAdminFee,
            ],
            'ownerDetailedReport' => $ownerDetailedReport,
            'detailedTotals' => $detailedTotals,
            'tenantsReport' => $tenantsReport,
        ];
    }
}
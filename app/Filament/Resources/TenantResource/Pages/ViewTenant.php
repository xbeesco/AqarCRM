<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Models\UnitContract;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;
    
    protected static ?string $title = 'عرض المستأجر';
    
    protected string $view = 'filament.resources.tenant-resource.pages.view-tenant';
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة التقرير')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->modalHeading('طباعة تقرير المستأجر')
                ->modalContent(function () {
                    $data = $this->getViewData();
                    return view('filament.resources.tenant-resource.pages.print-tenant', [
                        'tenant' => $this->record,
                        'reportData' => $data['reportData'],
                    ]);
                })
                ->modalWidth('5xl')
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
        $tenant = $this->record;
        
        // الحصول على العقد النشط الحالي
        $activeContract = UnitContract::where('tenant_id', $tenant->id)
            ->where('contract_status', 'active')
            ->with(['unit', 'unit.property', 'unit.unitType'])
            ->first();
        
        $reportData = [];
        
        if ($activeContract) {
            // تحويل تكرار الدفعات إلى العربية
            $paymentFrequency = $activeContract->payment_frequency;
            $paymentFrequencyAr = match($paymentFrequency) {
                'monthly' => 'شهري',
                'quarterly' => 'ربع سنوي',
                'semi_annual' => 'نصف سنوي',
                'annual' => 'سنوي',
                'yearly' => 'سنوي',
                default => $paymentFrequency ?? '-'
            };
            
            $reportData = [
                'tenant_name' => $tenant->name,
                'phone' => $tenant->phone ?? '-',
                'property_name' => $activeContract->unit?->property?->name ?? '-',
                'unit_number' => $activeContract->unit?->id ?? '-',
                'unit_type' => $activeContract->unit?->unitType?->name_ar ?? '-',
                'security_deposit' => $activeContract->security_deposit ?? 0,
                'payment_count' => $paymentFrequencyAr,
                'contract_start' => $activeContract->start_date,
                'notes' => $activeContract->notes ?? '-',
            ];
        } else {
            // إذا لم يكن هناك عقد نشط، نعرض بيانات فارغة
            $reportData = [
                'tenant_name' => $tenant->name,
                'phone' => $tenant->phone ?? '-',
                'property_name' => '-',
                'unit_number' => '-',
                'unit_type' => '-',
                'security_deposit' => 0,
                'payment_count' => '-',
                'contract_start' => '-',
                'notes' => '-',
            ];
        }
        
        return [
            'tenant' => $tenant,
            'reportData' => $reportData,
            'activeContract' => $activeContract,
        ];
    }
}
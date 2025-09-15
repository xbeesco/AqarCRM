<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use App\Filament\Resources\SupplyPaymentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PropertyContract;

class CreateSupplyPayment extends CreateRecord
{
    protected static string $resource = SupplyPaymentResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    public function getMaxContentWidth(): ?string
    {
        return 'full'; // يجعل المحتوى يأخذ العرض الكامل
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // إذا تم اختيار عقد، نحصل على owner_id منه
        if (!empty($data['property_contract_id'])) {
            $contract = PropertyContract::find($data['property_contract_id']);
            if ($contract) {
                $data['owner_id'] = $contract->owner_id;
            }
        }
        
        // تعيين قيم افتراضية للحقول المالية غير الموجودة في الفورم
        $data['gross_amount'] = $data['gross_amount'] ?? 0;
        $data['commission_amount'] = $data['commission_amount'] ?? 0;
        $data['commission_rate'] = $data['commission_rate'] ?? 0;
        $data['maintenance_deduction'] = $data['maintenance_deduction'] ?? 0;
        $data['other_deductions'] = $data['other_deductions'] ?? 0;
        $data['net_amount'] = $data['net_amount'] ?? 0;
        
        // تعيين الشهر والسنة للدفعة (الشهر الحالي افتراضياً)
        $data['month_year'] = $data['month_year'] ?? date('Y-m');
        
        // معالجة due_date حسب وجود paid_date
        if (!empty($data['paid_date'])) {
            // إذا تم التوريد (يوجد paid_date): نستخدم paid_date كـ due_date
            if (empty($data['due_date'])) {
                $data['due_date'] = $data['paid_date'];
            }
        } else {
            // إذا لم يتم التوريد بعد: due_date يأتي من الفورم
            // لكن نتأكد أنه موجود
            if (empty($data['due_date'])) {
                $data['due_date'] = now()->addDays(7); // افتراضي بعد 7 أيام
            }
        }
        
        return $data;
    }
}
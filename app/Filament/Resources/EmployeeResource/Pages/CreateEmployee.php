<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    public function mount(): void
    {
        // التحقق من الصلاحية قبل السماح بالوصول
        abort_unless(static::getResource()::canCreate(), 403);
        
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الموظف بنجاح';
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // التأكد من أن نوع المستخدم موظف
        $data['type'] = 'employee';
        
        return $data;
    }
}
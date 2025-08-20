<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء المستأجر بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Use phone1 as username
        $data['username'] = $data['phone1'];
        
        // Generate email from phone1 with towntop.sa domain
        $data['email'] = $data['phone1'] . '@towntop.sa';
        
        // Set password as phone1
        $data['password'] = bcrypt($data['phone1']);
        
        // Set user_type
        $data['user_type'] = 'tenant';
        
        return $data;
    }
}
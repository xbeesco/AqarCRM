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
        // Get domain from APP_URL without http://
        $appUrl = config('app.url');
        $domain = str_replace(['http://', 'https://'], '', $appUrl);
        
        // Use phone1 as username
        $data['username'] = $data['phone1'];
        
        // Generate email from phone1
        $data['email'] = $data['phone1'] . '@' . $domain;
        
        // Set password as phone1
        $data['password'] = $data['phone1'];
        
        // Set user_type
        $data['user_type'] = 'tenant';
        
        return $data;
    }
}
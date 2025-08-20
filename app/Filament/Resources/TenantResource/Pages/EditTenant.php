<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث بيانات المستأجر بنجاح';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Get domain from APP_URL without http://
        $appUrl = config('app.url');
        $domain = str_replace(['http://', 'https://'], '', $appUrl);
        
        // Use phone1 as username
        $data['username'] = $data['phone1'];
        
        // Generate email from phone1
        $data['email'] = $data['phone1'] . '@' . $domain;
        
        // Set password as phone1 if not provided
        if (empty($data['password'])) {
            $data['password'] = $data['phone1'];
        }
        
        // Set user_type
        $data['user_type'] = 'tenant';
        
        return $data;
    }
}
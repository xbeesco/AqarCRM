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
        // Use phone1 as username
        $data['username'] = $data['phone1'];
        
        // Generate email from phone1 with towntop.sa domain
        $data['email'] = $data['phone1'] . '@towntop.sa';
        
        // Don't change password if not provided
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        
        // Set user_type
        $data['user_type'] = 'tenant';
        
        return $data;
    }
}
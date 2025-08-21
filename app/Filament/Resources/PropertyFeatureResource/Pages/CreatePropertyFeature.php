<?php

namespace App\Filament\Resources\PropertyFeatureResource\Pages;

use App\Filament\Resources\PropertyFeatureResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePropertyFeature extends CreateRecord
{
    protected static string $resource = PropertyFeatureResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('تم إنشاء ميزة العقار بنجاح')
            ->body('تم حفظ ميزة العقار الجديدة.');
    }
}
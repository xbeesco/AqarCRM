<?php

namespace App\Filament\Resources\PropertyFeatureResource\Pages;

use App\Filament\Resources\PropertyFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPropertyFeature extends EditRecord
{
    protected static string $resource = PropertyFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('تم تحديث ميزة العقار بنجاح')
            ->body('تم حفظ التغييرات على ميزة العقار.');
    }
}
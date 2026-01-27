<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(static::getResource()::canEdit($this->resolveRecord($record)), 403);

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
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
        return 'تم تحديث بيانات الموظف بنجاح';
    }
}

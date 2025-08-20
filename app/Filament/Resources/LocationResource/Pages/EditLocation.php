<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // تحديث المسار عند تغيير الموقع الأب
        if (isset($data['parent_id']) && $data['parent_id'] !== $this->record->parent_id) {
            if ($data['parent_id']) {
                $parent = \App\Models\Location::find($data['parent_id']);
                if ($parent) {
                    $data['path'] = $parent->path ? $parent->path . '.' . $parent->id : (string)$parent->id;
                }
            } else {
                $data['path'] = null;
            }
        }
        
        return $data;
    }
}
<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static string $resource = LocationResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // إنشاء المسار التلقائي إذا لم يكن موجود
        if (empty($data['path']) && isset($data['parent_id'])) {
            $parent = \App\Models\Location::find($data['parent_id']);
            if ($parent) {
                $data['path'] = $parent->path ? $parent->path . '.' . $parent->id : (string)$parent->id;
            }
        }
        
        return $data;
    }
}
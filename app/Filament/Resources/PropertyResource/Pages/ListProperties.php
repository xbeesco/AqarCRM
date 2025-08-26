<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($search = $this->getTableSearch())) {
            $search = trim($search);
            
            // تطبيع البحث العربي الكامل
            $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
            $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);
            $normalizedSearch = str_replace(['ى'], 'ي', $normalizedSearch);
            $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
            
            $query->where(function (Builder $query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                // البحث في اسم وعنوان العقار
                $query->where('name', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('address', 'LIKE', "%{$normalizedSearch}%")
                    ->orWhere('address', 'LIKE', "%{$searchWithoutSpaces}%")
                    ->orWhere('postal_code', 'LIKE', "%{$search}%");
                
                // البحث في الأرقام
                if (is_numeric($search)) {
                    $query->orWhere('id', $search)
                        ->orWhere('parking_spots', 'LIKE', "%{$search}%")
                        ->orWhere('elevators', 'LIKE', "%{$search}%")
                        ->orWhere('build_year', 'LIKE', "%{$search}%")
                        ->orWhere('floors_count', 'LIKE', "%{$search}%");
                }
                
                // البحث في المالك
                $query->orWhereHas('owner', function ($q) use ($normalizedSearch, $searchWithoutSpaces, $search) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
                
                // البحث في الموقع
                $query->orWhereHas('location', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%");
                });
                
                // البحث في نوع العقار
                $query->orWhereHas('propertyType', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name_ar', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name_ar', 'LIKE', "%{$searchWithoutSpaces}%");
                });
                
                // البحث في حالة العقار
                $query->orWhereHas('propertyStatus', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name_ar', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name_ar', 'LIKE', "%{$searchWithoutSpaces}%");
                });
            });
        }
        
        return $query;
    }
}
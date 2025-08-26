<?php

namespace App\Filament\Resources\CollectionPaymentResource\Pages;

use App\Filament\Resources\CollectionPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListCollectionPayments extends ListRecords
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('دفعة تحصيل جديدة'),
        ];
    }
    
    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($search = $this->getTableSearch())) {
            $search = trim($search);
            
            // تطبيع البحث العربي
            $normalizedSearch = str_replace(['أ', 'إ', 'آ'], 'ا', $search);
            $normalizedSearch = str_replace(['ة'], 'ه', $normalizedSearch);
            $normalizedSearch = str_replace(['ى'], 'ي', $normalizedSearch);
            $searchWithoutSpaces = str_replace(' ', '', $normalizedSearch);
            
            $query->where(function (Builder $query) use ($search, $normalizedSearch, $searchWithoutSpaces) {
                // البحث في رقم الدفعة والمبلغ
                $query->where('payment_number', 'LIKE', "%{$search}%")
                    ->orWhere('payment_number', 'LIKE', "%{$searchWithoutSpaces}%");
                
                // البحث في المبلغ
                if (is_numeric($search)) {
                    $query->orWhere('amount', 'LIKE', "%{$search}%");
                }
                
                // البحث في المستأجر
                $query->orWhereHas('unitContract.tenant', function ($q) use ($normalizedSearch, $searchWithoutSpaces, $search) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
                
                // البحث في الوحدة
                $query->orWhereHas('unitContract.unit', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%");
                });
                
                // البحث في العقار
                $query->orWhereHas('unitContract.property', function ($q) use ($normalizedSearch, $searchWithoutSpaces) {
                    $q->where('name', 'LIKE', "%{$normalizedSearch}%")
                        ->orWhere('name', 'LIKE', "%{$searchWithoutSpaces}%");
                });
            });
        }
        
        return $query;
    }
}
<?php

namespace App\Filament\Resources\CollectionPaymentResource\Pages;

use App\Filament\Resources\CollectionPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Exports\CollectionPaymentsExport;
use Maatwebsite\Excel\Facades\Excel;

class ListCollectionPayments extends ListRecords
{
    protected static string $resource = CollectionPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('دفعة تحصيل جديدة'),
            Actions\Action::make('export')
                ->label('تصدير')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filename = 'دفعات-التحصيل-' . date('Y-m-d') . '.xlsx';
                    
                    return Excel::download(new CollectionPaymentsExport, $filename);
                }),
        ];
    }
    
    public function mount(): void
    {
        parent::mount();
        
        // Apply unit_contract_id filter from URL if present
        $unitContractId = request('unit_contract_id');
        if ($unitContractId) {
            $this->tableFilters['unit_contract_id']['value'] = $unitContractId;
        }
    }
    
    protected function applySearchToTableQuery(Builder $query): Builder
    {
        if (filled($search = $this->getTableSearch())) {
            $search = trim($search);
            
            $query->where(function (Builder $query) use ($search) {
                // البحث في الحقول الأساسية
                $query->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('payment_number', 'LIKE', "%{$search}%")
                    ->orWhere('amount', 'LIKE', "%{$search}%")
                    ->orWhere('delay_reason', 'LIKE', "%{$search}%")
                    ->orWhere('late_payment_notes', 'LIKE', "%{$search}%");
                
                // البحث في الحالة
                $statusMap = [
                    'محصل' => 'collected',
                    'مستحق' => 'due', 
                    'مؤجل' => 'postponed',
                    'متأخر' => 'overdue',
                ];
                
                foreach ($statusMap as $arabic => $english) {
                    if (str_contains(mb_strtolower($arabic), mb_strtolower($search))) {
                        $query->orWhere('collection_status', $english);
                    }
                }
                
                // البحث في رقم العقد
                $query->orWhereHas('unitContract', function ($q) use ($search) {
                    $q->where('contract_number', 'LIKE', "%{$search}%");
                });
                
                // البحث في المستأجر
                $query->orWhereHas('unitContract.tenant', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
                
                // البحث في الوحدة
                $query->orWhereHas('unitContract.unit', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
                
                // البحث في العقار
                $query->orWhereHas('unitContract.property', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
            });
        }
        
        return $query;
    }
}
<?php

namespace App\Filament\Resources\CollectionPaymentResource\Pages;

use App\Enums\PaymentStatus;
use App\Exports\CollectionPaymentsExport;
use App\Filament\Resources\CollectionPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListCollectionPayments extends ListRecords
{
    protected static string $resource = CollectionPaymentResource::class;

    protected ?int $unitContractId = null;

    protected ?int $propertyId = null;

    protected ?int $unitId = null;

    protected ?int $ownerId = null;

    protected ?int $tenantId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('تصدير')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $filename = 'دفعات-التحصيل-'.date('Y-m-d').'.xlsx';

                    return Excel::download(new CollectionPaymentsExport, $filename);
                }),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Get filter parameters from URL
        $this->unitContractId = request()->integer('unit_contract_id') ?: null;
        $this->propertyId = request()->integer('property_id') ?: null;
        $this->unitId = request()->integer('unit_id') ?: null;
        $this->ownerId = request()->integer('owner_id') ?: null;
        $this->tenantId = request()->integer('tenant_id') ?: null;

        // Apply filter if unit_contract_id exists
        if ($this->unitContractId) {
            $this->tableFilters['unit_contract_id'] = $this->unitContractId;
        }

        if ($this->propertyId) {
            $this->tableFilters['property_and_unit']['property_id'] = $this->propertyId;
            if ($this->unitId) {
                $this->tableFilters['property_and_unit']['unit_id'] = $this->unitId;
            }
        }

        if ($this->ownerId) {
            $this->tableFilters['owner']['owner_id'] = $this->ownerId;
        }

        if ($this->tenantId) {
            $this->tableFilters['tenant_id']['value'] = $this->tenantId;
        }
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // Apply unit_contract_id filter directly to query if present
        if ($this->unitContractId) {
            $query->where('unit_contract_id', $this->unitContractId);
        }

        return $query;
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

                // البحث في الحالة باستخدام الـ scopes الجديدة
                $statusMap = [
                    'محصل' => PaymentStatus::COLLECTED,
                    'مستحق' => PaymentStatus::DUE,
                    'مؤجل' => PaymentStatus::POSTPONED,
                    'متأخر' => PaymentStatus::OVERDUE,
                    'قادم' => PaymentStatus::UPCOMING,
                ];

                foreach ($statusMap as $arabic => $status) {
                    if (str_contains(mb_strtolower($arabic), mb_strtolower($search))) {
                        $query->orWhere(function ($subQuery) use ($status) {
                            $subQuery->byStatus($status);
                        });
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

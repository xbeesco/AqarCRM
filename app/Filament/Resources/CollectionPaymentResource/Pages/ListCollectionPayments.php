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

        // Get unit_contract_id from URL if present
        $this->unitContractId = request()->integer('unit_contract_id');

        // Apply filter if unit_contract_id exists
        if ($this->unitContractId) {
            // Set the filter value for the hidden filter
            $this->tableFilters['unit_contract_id'] = $this->unitContractId;
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
                // Search in basic fields
                $query->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('payment_number', 'LIKE', "%{$search}%")
                    ->orWhere('amount', 'LIKE', "%{$search}%")
                    ->orWhere('delay_reason', 'LIKE', "%{$search}%")
                    ->orWhere('late_payment_notes', 'LIKE', "%{$search}%");

                // Search by status using scopes
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

                // Search in contract number
                $query->orWhereHas('unitContract', function ($q) use ($search) {
                    $q->where('contract_number', 'LIKE', "%{$search}%");
                });

                // Search in tenant name
                $query->orWhereHas('unitContract.tenant', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });

                // Search in unit name
                $query->orWhereHas('unitContract.unit', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });

                // Search in property name
                $query->orWhereHas('unitContract.property', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
            });
        }

        return $query;
    }
}

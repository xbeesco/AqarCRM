<?php

namespace App\Filament\Resources\SupplyPayments\Pages;

use App\Filament\Resources\SupplyPayments\SupplyPaymentResource;
use App\Models\PropertyContract;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplyPayment extends CreateRecord
{
    protected static string $resource = SupplyPaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['property_contract_id'])) {
            $contract = PropertyContract::find($data['property_contract_id']);
            if ($contract) {
                $data['owner_id'] = $contract->owner_id;
            }
        }

        $data['gross_amount'] = $data['gross_amount'] ?? 0;
        $data['commission_amount'] = $data['commission_amount'] ?? 0;
        $data['commission_rate'] = $data['commission_rate'] ?? 0;
        $data['maintenance_deduction'] = $data['maintenance_deduction'] ?? 0;
        $data['other_deductions'] = $data['other_deductions'] ?? 0;
        $data['net_amount'] = $data['net_amount'] ?? 0;
        $data['month_year'] = $data['month_year'] ?? date('Y-m');

        if (! empty($data['paid_date'])) {
            if (empty($data['due_date'])) {
                $data['due_date'] = $data['paid_date'];
            }
        } else {
            if (empty($data['due_date'])) {
                $data['due_date'] = now()->addDays(7);
            }
        }

        return $data;
    }
}

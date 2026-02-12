<?php

namespace App\Filament\Resources\PropertyContracts\Pages;

use App\Filament\Resources\PropertyContracts\PropertyContractResource;
use App\Services\PaymentGeneratorService;
use App\Services\PropertyContractService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePropertyContract extends CreateRecord
{
    protected static string $resource = PropertyContractResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        // Calculate payments count before save
        $data['payments_count'] = PropertyContractService::calculatePaymentsCount(
            $data['duration_months'] ?? 0,
            $data['payment_frequency'] ?? 'monthly'
        );

        // Ensure value is numeric
        if (! is_numeric($data['payments_count'])) {
            $data['payments_count'] = 0;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // توليد الدفعات تلقائياً بعد إنشاء العقد
        try {
            $service = app(PaymentGeneratorService::class);
            $count = $service->generateSupplyPaymentsForContract($this->record);

            Notification::make()
                ->title('تم إنشاء العقد وتوليد الدفعات')
                ->body("تم توليد {$count} دفعة للعقد")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('تم إنشاء العقد')
                ->body('لكن فشل توليد الدفعات: '.$e->getMessage())
                ->warning()
                ->send();
        }
    }
}

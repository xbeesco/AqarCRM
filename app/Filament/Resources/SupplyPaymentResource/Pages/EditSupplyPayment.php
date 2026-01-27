<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use App\Filament\Resources\SupplyPaymentResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplyPayment extends EditRecord
{
    protected static string $resource = SupplyPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    /**
     * Redirect to view page instead of edit.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
    }
}

<?php

namespace App\Filament\Resources\SupplyPaymentResource\Pages;

use App\Filament\Resources\SupplyPaymentResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;

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
        return 'full'; // يجعل المحتوى يأخذ العرض الكامل
    }
    
    // منع التعديل المباشر - إعادة التوجيه إلى صفحة العرض
    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // إعادة التوجيه إلى صفحة العرض بدلاً من التعديل
        $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
    }
}
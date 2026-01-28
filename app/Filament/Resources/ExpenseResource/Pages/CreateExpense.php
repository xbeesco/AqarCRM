<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use Filament\Notifications\Notification;
use App\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء النفقة بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle polymorphic relationship
        if (isset($data['expense_for']) && isset($data['property_id'])) {
            switch ($data['expense_for']) {
                case 'property':
                    // General expense for property - save property as subject
                    $data['subject_type'] = 'App\Models\Property';
                    $data['subject_id'] = $data['property_id'];
                    break;

                case 'unit':
                    if (isset($data['unit_id']) && $data['unit_id'] !== '0') {
                        // Unit-specific expense - save unit as subject
                        $data['subject_type'] = 'App\Models\Unit';
                        $data['subject_id'] = $data['unit_id'];
                    } else {
                        // If no valid unit selected, fall back to property expense
                        $data['subject_type'] = 'App\Models\Property';
                        $data['subject_id'] = $data['property_id'];

                        // Notify user about the fallback
                        Notification::make()
                            ->warning()
                            ->title('تنبيه')
                            ->body('تم حفظ النفقة كنفقة عامة للعقار لعدم اختيار وحدة صحيحة')
                            ->send();
                    }
                    break;
            }
        }

        // Remove helper fields
        unset($data['expense_for'], $data['property_id'], $data['unit_id']);

        return $data;
    }
}

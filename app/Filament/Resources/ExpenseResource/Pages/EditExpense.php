<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Models\Unit;
use Filament\Notifications\Notification;
use App\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث النفقة بنجاح';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert polymorphic data back to form fields
        if (isset($data['subject_type']) && isset($data['subject_id'])) {
            switch ($data['subject_type']) {
                case 'App\Models\Property':
                    // General property expense
                    $data['expense_for'] = 'property';
                    $data['property_id'] = $data['subject_id'];
                    break;

                case 'App\Models\Unit':
                    // Unit-specific expense
                    $data['expense_for'] = 'unit';
                    $data['unit_id'] = $data['subject_id'];

                    // Get the property_id for the unit
                    $unit = Unit::find($data['subject_id']);
                    if ($unit) {
                        $data['property_id'] = $unit->property_id;
                    }
                    break;
            }
        } else {
            // Default to property expense if no subject
            $data['expense_for'] = 'property';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

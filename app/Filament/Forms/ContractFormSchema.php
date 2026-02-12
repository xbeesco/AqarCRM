<?php

namespace App\Filament\Forms;

use App\Services\ContractValidationService;
use App\Services\PropertyContractService;
use App\Services\PropertyContractValidationService;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ContractFormSchema
{
    public static function getDurationFields(string $type = 'unit', $record = null): array
    {
        return [
            TextInput::make($record ? 'additional_months' : 'duration_months')
                ->label($record ? 'المدة المعاد جدولتها' : 'مدة التعاقد بالشهر')
                ->numeric()
                ->required()
                ->minValue(1)
                ->suffix('شهر')
                ->live()
                ->afterStateUpdated(function ($state, $get, $set) use ($record) {
                    $frequency = $get($record ? 'new_frequency' : 'payment_frequency') ?? 'monthly';
                    $count = PropertyContractService::calculatePaymentsCount($state ?? 0, $frequency);
                    $set($record ? 'new_payments_count' : 'payments_count', $count);

                    // إظهار تنبيه عند إدخال قيمة غير صالحة
                    if (($state ?? 0) < 1) {
                        \Filament\Notifications\Notification::make()
                            ->title('خطأ في المدة')
                            ->body('يجب أن تكون المدة شهر واحد على الأقل')
                            ->danger()
                            ->send();
                    }
                })
                ->rules([
                    fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get, $type, $record) {
                        $frequency = $get($record ? 'new_frequency' : 'payment_frequency') ?? 'monthly';

                        // 1. التحقق من توافق المدة مع دورية الدفع
                        if (! PropertyContractService::isValidDuration($value ?? 0, $frequency)) {
                            $periodName = match ($frequency) {
                                'monthly' => 'شهر',
                                'quarterly' => 'ربع سنة',
                                'semi_annually' => 'نصف سنة',
                                'annually' => 'سنة',
                                default => 'شهر',
                            };
                            $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");

                            return;
                        }

                        // 2. التحقق من عدم التداخل مع عقود مستقبلية
                        $startDate = $get('start_date');
                        $idAttribute = $type === 'unit' ? 'unit_id' : 'property_id';
                        $id = $get($idAttribute);

                        // في حالة إعادة الجدولة/التجديد، تاريخ البداية للفترة الجديدة يكون بعد الأشهر المدفوعة
                        if ($record) {
                            $paidMonths = $record->getPaidMonthsCount();
                            $startDate = $record->start_date->copy()->addMonths($paidMonths);
                        }

                        if ($id && $startDate && $value) {
                            if ($type === 'unit') {
                                $validationService = app(ContractValidationService::class);
                                $error = $validationService->validateDuration($id, $startDate, $value, $record?->id);
                            } else {
                                $validationService = app(PropertyContractValidationService::class);
                                $error = $validationService->validateDuration($id, $startDate, $value, $record?->id);
                            }

                            if ($error) {
                                $fail($error);
                            }
                        }
                    },
                ])
                ->columnSpan(3),

            Select::make($record ? 'new_frequency' : 'payment_frequency')
                ->label(function () use ($type, $record) {
                    $action = $type === 'property' ? 'توريد' : 'تحصيل';

                    return $record ? "{$action} تلك المدة سيكون كل" : "{$action} كل";
                })
                ->required()
                ->options([
                    'monthly' => 'شهر',
                    'quarterly' => 'ربع سنة',
                    'semi_annually' => 'نصف سنة',
                    'annually' => 'سنة',
                ])
                ->default('monthly')
                ->live()
                ->afterStateUpdated(function ($state, $get, $set) use ($record) {
                    $duration = $get($record ? 'additional_months' : 'duration_months') ?? 0;
                    $count = PropertyContractService::calculatePaymentsCount($duration, $state ?? 'monthly');
                    $set($record ? 'new_payments_count' : 'payments_count', $count);
                })
                ->columnSpan(3),

            TextInput::make($record ? 'new_payments_count' : 'payments_count')
                ->label('عدد الدفعات')
                ->disabled()
                ->dehydrated(false)
                ->default(function ($get) use ($record) {
                    $duration = $get($record ? 'additional_months' : 'duration_months') ?? 0;
                    $frequency = $get($record ? 'new_frequency' : 'payment_frequency') ?? 'monthly';

                    return PropertyContractService::calculatePaymentsCount($duration, $frequency);
                })
                ->columnSpan(3),
        ];
    }
}

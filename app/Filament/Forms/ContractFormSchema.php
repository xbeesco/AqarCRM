<?php

namespace App\Filament\Forms;

use App\Models\PropertyContract;
use App\Models\UnitContract;
use App\Services\ContractValidationService;
use App\Services\PropertyContractService;
use App\Services\PropertyContractValidationService;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ContractFormSchema
{
    public static function getDurationFields(string $type = 'unit', $record = null): array
    {
        return [
            TextInput::make($record ? 'additional_months' : 'duration_months')
                ->label($record ? 'المدة الجديدة (إجمالي المتبقي + الجديد)' : 'مدة التعاقد بالشهر')
                ->numeric()
                ->required()
                ->minValue(1)
                ->suffix('شهر')
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $get, $set) use ($record) {
                    $frequency = $get($record ? 'new_frequency' : 'payment_frequency') ?? 'monthly';
                    $count = PropertyContractService::calculatePaymentsCount($state ?? 0, $frequency);
                    $set($record ? 'new_payments_count' : 'payments_count', $count);
                })
                ->rules([
                    fn($get): Closure => function (string $attribute, $value, Closure $fail) use ($get, $type, $record) {
                        $frequency = $get($record ? 'new_frequency' : 'payment_frequency') ?? 'monthly';

                        // 1. Validate Frequency Compatibility
                        if (!PropertyContractService::isValidDuration($value ?? 0, $frequency)) {
                            $periodName = match ($frequency) {
                                'quarterly' => 'ربع سنة',
                                'semi_annually' => 'نصف سنة',
                                'annually' => 'سنة',
                                default => $frequency,
                            };
                            $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");
                            return;
                        }

                        // 2. Validate Future Overlap
                        $startDate = $get('start_date');
                        $idAttribute = $type === 'unit' ? 'unit_id' : 'property_id';
                        $id = $get($idAttribute);

                        // If rescheduling/renewing, the start date for the "new" period is after the paid months
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
                ->label($record ? 'تكرار التحصيل للمدة الجديدة' : 'تكرار التحصيل')
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

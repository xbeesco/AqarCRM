<?php

namespace App\Filament\Resources\PropertyContracts\Schemas;

use App\Models\Property;
use App\Services\PropertyContractService;
use App\Services\PropertyContractValidationService;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PropertyContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات العقد')
                    ->schema([
                        Select::make('property_id')
                            ->label('العقار')
                            ->required()
                            ->searchable()
                            ->relationship('property', 'name')
                            ->options(Property::with('owner')->get()->pluck('name', 'id'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' - '.$record->owner?->name)
                            ->columnSpan(6),

                        TextInput::make('commission_rate')
                            ->label('النسبة المئوية')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->columnSpan(6),

                        DatePicker::make('start_date')
                            ->label('تاريخ بداية العمل بالعقد')
                            ->required()
                            ->default(now())
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'date',
                                fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $propertyId = $get('property_id');
                                    if (! $propertyId || ! $value) {
                                        return;
                                    }

                                    $validationService = app(PropertyContractValidationService::class);
                                    $excludeId = $record ? $record->id : null;

                                    // Validate start date only
                                    $error = $validationService->validateStartDate($propertyId, $value, $excludeId);
                                    if ($error) {
                                        $fail($error);
                                    }
                                },
                            ])
                            ->columnSpan(3),

                        TextInput::make('duration_months')
                            ->label('مدة التعاقد بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('شهر')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $frequency = $get('payment_frequency') ?? 'monthly';
                                $count = PropertyContractService::calculatePaymentsCount($state ?? 0, $frequency);
                                $set('payments_count', $count);
                            })
                            ->rules([
                                fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    // Validate duration matches frequency
                                    $frequency = $get('payment_frequency') ?? 'monthly';
                                    if (! PropertyContractService::isValidDuration($value ?? 0, $frequency)) {
                                        $periodName = match ($frequency) {
                                            'quarterly' => 'ربع سنة',
                                            'semi_annually' => 'نصف سنة',
                                            'annually' => 'سنة',
                                            default => $frequency,
                                        };

                                        $fail("عدد الاشهر هذا لا يقبل القسمة علي {$periodName}");

                                        return;
                                    }

                                    // Validate no overlap with other contracts
                                    $propertyId = $get('property_id');
                                    $startDate = $get('start_date');

                                    if ($propertyId && $startDate && $value) {
                                        $validationService = app(PropertyContractValidationService::class);
                                        $excludeId = $record ? $record->id : null;

                                        // Validate duration and its effect on end date
                                        $error = $validationService->validateDuration($propertyId, $startDate, $value, $excludeId);
                                        if ($error) {
                                            $fail($error);
                                        }
                                    }
                                },
                            ])
                            ->validationAttribute('مدة التعاقد')
                            ->columnSpan(3),

                        Select::make('payment_frequency')
                            ->label('التوريد كل')
                            ->required()
                            ->options([
                                'monthly' => 'شهر',
                                'quarterly' => 'ربع سنة',
                                'semi_annually' => 'نصف سنة',
                                'annually' => 'سنة',
                            ])
                            ->default('monthly')
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $duration = $get('duration_months') ?? 0;
                                $count = PropertyContractService::calculatePaymentsCount($duration, $state ?? 'monthly');
                                $set('payments_count', $count);
                            })
                            ->columnSpan(3),

                        TextInput::make('payments_count')
                            ->label('عدد الدفعات')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($get) {
                                $duration = $get('duration_months') ?? 0;
                                $frequency = $get('payment_frequency') ?? 'monthly';
                                $result = PropertyContractService::calculatePaymentsCount($duration, $frequency);

                                return $result;
                            })
                            ->columnSpan(3),

                        FileUpload::make('file')
                            ->label('ملف العقد')
                            ->required()
                            ->directory('property-contract--file')
                            ->columnSpan(6),

                        Textarea::make('notes')
                            ->label('الملاحظات')
                            ->rows(3)
                            ->columnSpan(6),
                    ])
                    ->columns(12)
                    ->columnSpanFull(),
            ]);
    }
}

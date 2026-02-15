<?php

namespace App\Filament\Resources\UnitContracts\Schemas;

use App\Models\Unit;
use App\Models\User;
use App\Services\ContractValidationService;
use App\Services\PropertyContractService;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitContractForm
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
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                $set('unit_id', null);
                                if ($state) {
                                    $units = Unit::where('property_id', $state)->get();
                                    if ($units->count() === 1) {
                                        $unit = $units->first();
                                        $set('unit_id', $unit->id);
                                        $set('monthly_rent', $unit->rent_price ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(3),

                        Select::make('unit_id')
                            ->label('الوحدة')
                            ->required()
                            ->options(function (callable $get) {
                                $propertyId = $get('property_id');
                                if (! $propertyId) {
                                    return [];
                                }

                                return Unit::where('property_id', $propertyId)->pluck('name', 'id');
                            })
                            ->disabled(fn (callable $get): bool => ! $get('property_id'))
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $unit = Unit::find($state);
                                    if ($unit) {
                                        $set('monthly_rent', $unit->rent_price ?? 0);
                                    }
                                }
                            })
                            ->columnSpan(3),

                        TextInput::make('monthly_rent')
                            ->label('قيمة الإيجار بالشهر')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->postfix('ريال')
                            ->columnSpan(3),

                        Select::make('tenant_id')
                            ->label('المستأجر')
                            ->required()
                            ->searchable()
                            ->options(fn () => User::where('type', 'tenant')->pluck('name', 'id'))
                            ->columnSpan(3),

                        DatePicker::make('start_date')
                            ->label('تاريخ بداية العمل بالعقد')
                            ->required()
                            ->default(now())
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'date',
                                fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $unitId = $get('unit_id');
                                    if (! $unitId || ! $value) {
                                        return;
                                    }
                                    $validationService = app(ContractValidationService::class);
                                    $error = $validationService->validateStartDate($unitId, $value, $record?->id);
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
                                $count = PropertyContractService::calculatePaymentsCount($state ?? 0, $get('payment_frequency') ?? 'monthly');
                                $set('payments_count', $count);
                            })
                            ->rules([
                                fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $frequency = $get('payment_frequency') ?? 'monthly';
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
                                    $unitId = $get('unit_id');
                                    $startDate = $get('start_date');
                                    if ($unitId && $startDate && $value) {
                                        $validationService = app(ContractValidationService::class);
                                        $error = $validationService->validateDuration($unitId, $startDate, $value, $record?->id);
                                        if ($error) {
                                            $fail($error);
                                        }
                                    }
                                },
                            ])
                            ->columnSpan(3),

                        Select::make('payment_frequency')
                            ->label('التحصيل كل')
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
                                $count = PropertyContractService::calculatePaymentsCount($get('duration_months') ?? 0, $state ?? 'monthly');
                                $set('payments_count', $count);
                            })
                            ->columnSpan(3),

                        TextInput::make('payments_count')
                            ->label('عدد الدفعات')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($get) {
                                return PropertyContractService::calculatePaymentsCount($get('duration_months') ?? 0, $get('payment_frequency') ?? 'monthly');
                            })
                            ->columnSpan(3),

                        FileUpload::make('file')
                            ->label('ملف العقد')
                            ->directory('unit-contract--file')
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

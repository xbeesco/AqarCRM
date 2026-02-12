<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use App\Models\Property;
use App\Models\Unit;
use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('بيانات النفقة')
                ->schema([
                    Textarea::make('desc')
                        ->label('التوصيف')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->schema([
                            Select::make('type')
                                ->label('نوع النفقة')
                                ->required()
                                ->options(Expense::TYPES)
                                ->native(false),

                            Select::make('property_id')
                                ->label('العقار')
                                ->options(Property::query()
                                    ->with('owner')
                                    ->get()
                                    ->mapWithKeys(fn ($property) => [
                                        $property->id => $property->name.' - '.$property->owner->name,
                                    ])
                                    ->toArray()
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, $get, $state) {
                                    // Clear unit selection when property changes
                                    $set('unit_id', null);
                                    // إعادة التحقق من التاريخ عند تغيير العقار
                                    if ($get('date')) {
                                        $set('date', $get('date'));
                                    }
                                })
                                ->native(false),

                            Select::make('expense_for')
                                ->label('النفقة خاصة بـ')
                                ->required()
                                ->options([
                                    'property' => 'العقار ككل',
                                    'unit' => 'وحدة معينة',
                                ])
                                ->default('property')
                                ->live()
                                ->afterStateUpdated(function (Set $set, $get, $state) {
                                    if ($state !== 'unit') {
                                        $set('unit_id', null);
                                    } else {
                                        // إذا اختار وحدة معينة ولكن العقار ليس له وحدات، غيّر الاختيار للعقار ككل
                                        $propertyId = $get('property_id');
                                        if ($propertyId) {
                                            $unitsCount = Unit::where('property_id', $propertyId)->count();
                                            if ($unitsCount === 0) {
                                                $set('expense_for', 'property');
                                                \Filament\Notifications\Notification::make()
                                                    ->warning()
                                                    ->title('تنبيه')
                                                    ->body('هذا العقار ليس له وحدات، تم تغيير النفقة لتكون للعقار ككل')
                                                    ->send();
                                            }
                                        }
                                    }
                                })
                                ->rules([
                                    fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        if ($value === 'unit' && $get('property_id')) {
                                            $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                            if ($unitsCount === 0) {
                                                $fail('هذا العقار ليس له وحدات، يجب اختيار "العقار ككل"');
                                            }
                                        }
                                    },
                                ])
                                ->native(false),

                            Select::make('unit_id')
                                ->label('الوحدة')
                                ->placeholder(fn ($get): string => ! $get('property_id') ? 'اختر العقار أولاً' : 'اختر الوحدة'
                                )
                                ->options(function ($get): array {
                                    $propertyId = $get('property_id');
                                    if (! $propertyId) {
                                        return [];
                                    }
                                    $units = Unit::where('property_id', $propertyId)
                                        ->get()
                                        ->pluck('name', 'id')
                                        ->toArray();

                                    if (empty($units)) {
                                        return ['0' => 'لا توجد وحدات متاحة لهذا العقار'];
                                    }

                                    return $units;
                                })
                                ->disabled(function ($get): bool {
                                    if ($get('expense_for') !== 'unit') {
                                        return true;
                                    }
                                    if (! $get('property_id')) {
                                        return true;
                                    }
                                    $unitsCount = Unit::where('property_id', $get('property_id'))->count();

                                    return $unitsCount === 0;
                                })
                                ->helperText(function ($get): ?string {
                                    if ($get('expense_for') !== 'unit') {
                                        return null;
                                    }
                                    if (! $get('property_id')) {
                                        return 'اختر العقار أولاً';
                                    }
                                    $unitsCount = Unit::where('property_id', $get('property_id'))->count();
                                    if ($unitsCount === 0) {
                                        return '⚠️ لا توجد وحدات مضافة لهذا العقار';
                                    }

                                    return 'عدد الوحدات المتاحة: '.$unitsCount;
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->visible(fn ($get): bool => $get('expense_for') === 'unit')
                                ->required(fn ($get): bool => $get('expense_for') === 'unit' && Unit::where('property_id', $get('property_id'))->exists())
                                ->rules([
                                    fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                        if ($get('expense_for') === 'unit' && ! $value) {
                                            $propertyId = $get('property_id');
                                            if ($propertyId && Unit::where('property_id', $propertyId)->exists()) {
                                                $fail('يجب اختيار وحدة');
                                            }
                                        }
                                        // التحقق من أن الوحدة المختارة تنتمي للعقار المختار
                                        if ($value && $value !== '0' && $get('property_id')) {
                                            $unit = Unit::find($value);
                                            if ($unit && $unit->property_id != $get('property_id')) {
                                                $fail('الوحدة المختارة لا تنتمي لهذا العقار');
                                            }
                                        }
                                    },
                                ])
                                ->native(false),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('cost')
                                ->label('الإجمالي')
                                ->required()
                                ->numeric()
                                ->prefix('ريال')
                                ->step(0.01),

                            DatePicker::make('date')
                                ->label('التاريخ')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->live(onBlur: true)
                                ->rules([
                                    'required',
                                    'date',
                                    fn ($get, $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                        $propertyId = $get('property_id');
                                        $unitId = $get('unit_id');
                                        if (! $propertyId || ! $value) {
                                            return;
                                        }

                                        $validationService = app(\App\Services\ExpenseValidationService::class);
                                        $excludeId = $record ? $record->id : null;
                                        $expenseFor = $get('expense_for') ?? 'property';

                                        // استخدام نفس الطريقة مع المعاملات الصحيحة
                                        $error = $validationService->validateExpense($expenseFor, $propertyId, $unitId, $value, $excludeId);
                                        if ($error) {
                                            $fail($error);
                                        }
                                    },
                                ])
                                ->validationAttribute('التاريخ'),
                        ]),
                ]),

            Section::make('الإثباتات والوثائق')
                ->schema([
                    Builder::make('docs')
                        ->label('الإثباتات')
                        ->blocks([
                            Builder\Block::make('purchase_invoice')
                                ->label('فاتورة مشتريات')
                                ->icon('heroicon-o-receipt-percent')
                                ->schema([
                                    TextInput::make('type')
                                        ->label('اسم الفاتورة')
                                        ->placeholder('مشتريات السباكة, دهانات المدخل, الخ ...'),

                                    TextInput::make('amount')
                                        ->label('المبلغ')
                                        ->numeric()
                                        ->prefix('ريال'),

                                    FileUpload::make('file')
                                        ->label('الملف')
                                        ->directory('expense--file')
                                        ->columnSpanFull()
                                        ->required(),

                                ])->columns(2),

                            Builder\Block::make('labor_invoice')
                                ->label('فاتورة عمل يد')
                                ->icon('heroicon-o-wrench-screwdriver')
                                ->schema([
                                    TextInput::make('type')
                                        ->label('نوع العمل')
                                        ->Placeholder('تصليح كهرباء, سباكة, تنظيف, الخ ...'),

                                    TextInput::make('amount')
                                        ->label('المبلغ')
                                        ->numeric()
                                        ->prefix('ريال'),

                                    FileUpload::make('file')
                                        ->label('الملف')
                                        ->directory('expense--file')
                                        ->columnSpanFull()
                                        ->required(),
                                ])->columns(2),

                            Builder\Block::make('government_document')
                                ->label('وثيقة حكومية')
                                ->icon('heroicon-o-building-office')
                                ->schema([
                                    TextInput::make('type')
                                        ->label('نوع الوثيقة')
                                        ->placeholder('تسوية ضريبية, فاتورة الكهرباء, إلخ...'),
                                    TextInput::make('amount')
                                        ->label('المبلغ')
                                        ->numeric()
                                        ->prefix('ريال'),

                                    TextInput::make('entity')
                                        ->label('الجهة الحكومية'),

                                    FileUpload::make('file')
                                        ->label('الملف')
                                        ->directory('expense--file')
                                        ->columnSpanFull()
                                        ->required(),
                                ])->columns(2),
                        ])
                        ->addActionLabel('إضافة إثبات')
                        ->collapsible()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

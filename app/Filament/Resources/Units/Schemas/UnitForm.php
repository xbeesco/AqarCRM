<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('المعلومات الأساسية')
                ->columnSpanFull()
                ->columns(12)
                ->schema([
                    Select::make('property_id')
                        ->label('العقار')
                        ->relationship('property', 'name')
                        ->searchable()
                        ->required()
                        ->preload()
                        ->disabled(fn (string $operation): bool => $operation === 'edit')
                        ->dehydrated()
                        ->columnSpan(3),

                    Select::make('unit_type_id')
                        ->label('نوع الوحدة')
                        ->relationship('unitType', 'name')
                        ->required()
                        ->native(false)
                        ->columnSpan(3),

                    TextInput::make('name')
                        ->label('اسم الوحدة')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),

                    Select::make('unit_category_id')
                        ->label('تصنيف الوحدة')
                        ->relationship('unitCategory', 'name')
                        ->required()
                        ->native(false)
                        ->columnSpan(3),
                ]),

            Section::make('التفاصيل')
                ->columnSpan(1)
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('rent_price')
                            ->label('الايجار الشهري الاستدلالي')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('ريال'),
                        TextInput::make('floor_number')
                            ->label('رقم الطابق')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable(),
                        TextInput::make('area_sqm')
                            ->label('المساحة')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('م²')
                            ->nullable(),

                        TextInput::make('rooms_count')
                            ->label('عدد الغرف')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->nullable(),

                        TextInput::make('bathrooms_count')
                            ->label('عدد دورات المياه')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->nullable(),

                        TextInput::make('balconies_count')
                            ->label('عدد الشرفات')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->nullable(),

                        Select::make('has_laundry_room')
                            ->label('غرفة غسيل')
                            ->options([
                                1 => 'نعم',
                                0 => 'لا',
                            ])
                            ->required()
                            ->default(0),

                        TextInput::make('electricity_account_number')
                            ->label('رقم حساب الكهرباء')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('water_expenses')
                            ->label('مصروف المياه')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('ريال')
                            ->nullable(),

                    ]),
                ]),

            Section::make('المميزات')
                ->columnSpan(1)
                ->schema([
                    CheckboxList::make('features')
                        ->label('مميزات الوحدة')
                        ->hiddenLabel()
                        ->relationship('features', 'name')
                        ->columns(3),
                ]),

            Section::make('المخططات والملاحظات')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([
                        FileUpload::make('floor_plan_file')
                            ->label('مخطط الوحدة')
                            ->directory('unit--floor-plan-file')
                            ->nullable(),

                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->maxLength(65535)
                            ->rows(3),
                    ]),
                ]),
        ]);
    }
}

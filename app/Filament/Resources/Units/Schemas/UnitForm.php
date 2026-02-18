<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Services\CustomFieldRenderer;
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

                    ...CustomFieldRenderer::formComponents('unit', 'basic_info'),
                ]),

            Section::make('التفاصيل')
                ->columnSpan(1)
                ->schema([
                    TextInput::make('rent_price')
                        ->label('الايجار الشهري الاستدلالي')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->prefix('ريال'),

                    ...CustomFieldRenderer::formComponents('unit', 'details'),
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

                    ...CustomFieldRenderer::formComponents('unit', 'plans_notes'),
                ]),
        ]);
    }
}

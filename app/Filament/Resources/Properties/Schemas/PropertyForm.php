<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Models\Location;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Services\CustomFieldRenderer;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('البيانات الأساسية')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('اسم العقار')
                            ->required()
                            ->columnSpan(1),

                        Select::make('owner_id')
                            ->label('المالك')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated()
                            ->columnSpan(1),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('status_id')
                            ->label('حالة العقار')
                            ->options(PropertyStatus::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('type_id')
                            ->label('نوع العقار')
                            ->options(PropertyType::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ]),

                    ...CustomFieldRenderer::formComponents('property', 'basic_data'),
                ]),

            Section::make('الموقع والعنوان')
                ->schema([
                    Select::make('location_id')
                        ->label('الموقع')
                        ->options(Location::getHierarchicalOptions())
                        ->searchable()
                        ->allowHtml()
                        ->nullable(),

                    Grid::make(2)->schema([
                        TextInput::make('address')
                            ->label('رقم المبنى واسم الشارع')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('postal_code')
                            ->label('الرمز البريدي')
                            ->numeric()
                            ->columnSpan(1),
                    ]),

                    ...CustomFieldRenderer::formComponents('property', 'location_address'),
                ]),

            Section::make('تفاصيل إضافية')
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)->schema([
                        CheckboxList::make('features')
                            ->label('المميزات')
                            ->relationship('features', 'name')
                            ->columns(4)
                            ->columnSpan(1),

                        Textarea::make('notes')
                            ->label('ملاحظات خاصة')
                            ->rows(6)
                            ->columnSpan(1),
                    ]),

                    ...CustomFieldRenderer::formComponents('property', 'additional_details'),
                ]),
        ]);
    }
}

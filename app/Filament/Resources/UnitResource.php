<?php

namespace App\Filament\Resources;

use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UnitResource\Pages\ListUnits;
use App\Filament\Resources\UnitResource\Pages\CreateUnit;
use App\Filament\Resources\UnitResource\Pages\ViewUnit;
use App\Filament\Resources\UnitResource\Pages\EditUnit;
use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationLabel = 'الوحدات';

    protected static ?string $modelLabel = 'وحدة';

    protected static ?string $pluralModelLabel = 'الوحدات';

    public static function form(Schema $schema): Schema
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
                        ->columnSpan(3),

                    Select::make('unit_type_id')
                        ->label('نوع الوحدة')
                        ->relationship('unitType', 'name_ar')
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
                        ->relationship('unitCategory', 'name_ar')
                        ->required()
                        ->native(false)
                        ->columnSpan(3),
                ]),

            Section::make('التفاصيل')
                ->columnSpan(1)
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('rent_price')
                            ->label('سعر الايجار الاستدلالي')
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
                        ->relationship('features', 'name_ar')
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

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with([
                    'property.location.parent.parent.parent',
                    'unitType',
                    'unitCategory',
                ]);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable()
                    ->tooltip(function ($record): ?string {
                        if (! $record->property || ! $record->property->location) {
                            return null;
                        }

                        $path = [];
                        $current = $record->property->location;

                        while ($current) {
                            array_unshift($path, $current->name);
                            $current = $current->parent;
                        }

                        return 'الموقع: '.implode(' > ', $path);
                    }),

                TextColumn::make('unitType.name_ar')
                    ->label('نوع الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unitCategory.name_ar')
                    ->label('تصنيف الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('area_sqm')
                    ->label('المساحة')
                    ->suffix(' م²')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('rooms_count')
                    ->label('عدد الغرف')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('bathrooms_count')
                    ->label('دورات المياه')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('rent_price')
                    ->label('الإيجار الشهري')
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state).' ريال' : '-')
                    ->searchable(query: function ($query, $search) {
                        $monthlyRent = (float) str_replace(',', '', $search);
                        $yearlyRent = $monthlyRent / 12;

                        return $query
                            ->orWhere('rent_price', 'like', '%'.$search.'%')
                            ->orWhere('rent_price', $monthlyRent)
                            ->orWhere('rent_price', $yearlyRent);
                    })
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'view' => ViewUnit::route('/{record}'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}

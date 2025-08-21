<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyStatusResource\Pages;
use App\Models\PropertyStatus;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyStatusResource extends Resource
{
    protected static ?string $model = PropertyStatus::class;

    protected static ?string $navigationLabel = 'حالات العقارات';

    protected static ?string $modelLabel = 'حالة عقار';

    protected static ?string $pluralModelLabel = 'حالات العقارات';

    // Navigation properties removed - managed centrally in AdminPanelProvider

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name_ar')
                                    ->label('Arabic Name')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('name_en')
                                    ->label('English Name')
                                    ->required()
                                    ->maxLength(100),
                            ]),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(120)
                            ->unique(PropertyStatus::class, 'slug', ignoreRecord: true)
                            ->regex('/^[a-z0-9-]+$/'),
                        Grid::make(2)
                            ->schema([
                                ColorPicker::make('color')
                                    ->label('Color')
                                    ->default('gray'),
                                TextInput::make('icon')
                                    ->label('Icon')
                                    ->maxLength(50)
                                    ->default('heroicon-o-home'),
                            ]),
                    ]),
                Section::make('Descriptions')
                    ->schema([
                        Textarea::make('description_ar')
                            ->label('Arabic Description')
                            ->maxLength(1000),
                        Textarea::make('description_en')
                            ->label('English Description')
                            ->maxLength(1000),
                    ]),
                Section::make('Settings')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_available')
                                    ->label('Available')
                                    ->default(true),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('Arabic Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label('English Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('color')
                    ->label('Color')
                    ->sortable(),
                IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),
                TextColumn::make('properties_count')
                    ->label('Properties Count')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                TernaryFilter::make('is_available')
                    ->label('Available Status'),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropertyStatuses::route('/'),
            'create' => Pages\CreatePropertyStatus::route('/create'),
            'edit' => Pages\EditPropertyStatus::route('/{record}/edit'),
        ];
    }
}

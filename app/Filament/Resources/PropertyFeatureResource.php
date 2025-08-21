<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyFeatureResource\Pages;
use App\Models\PropertyFeature;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyFeatureResource extends Resource
{
    protected static ?string $model = PropertyFeature::class;

    protected static ?string $navigationLabel = 'مميزات العقارات';

    protected static ?string $modelLabel = 'ميزة عقار';

    protected static ?string $pluralModelLabel = 'مميزات العقارات';

    // Navigation properties removed - managed centrally in AdminPanelProvider


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
                        Select::make('category')
                            ->label('Category')
                            ->required()
                            ->options([
                                'basics' => 'Basics / أساسيات',
                                'amenities' => 'Amenities / مرافق',
                                'security' => 'Security / أمان',
                                'extras' => 'Extras / إضافات',
                            ])
                            ->default('basics'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->label('Arabic Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name_en')
                    ->label('English Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basics' => 'gray',
                        'amenities' => 'success',
                        'security' => 'warning',
                        'extras' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'basics' => 'Basics / أساسيات',
                        'amenities' => 'Amenities / مرافق',
                        'security' => 'Security / أمان',
                        'extras' => 'Extras / إضافات',
                        default => $state,
                    })
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
                SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'basics' => 'Basics / أساسيات',
                        'amenities' => 'Amenities / مرافق',
                        'security' => 'Security / أمان',
                        'extras' => 'Extras / إضافات',
                    ]),
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
            ->defaultSort('category')
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropertyFeatures::route('/'),
            'create' => Pages\CreatePropertyFeature::route('/create'),
            'edit' => Pages\EditPropertyFeature::route('/{record}/edit'),
        ];
    }
}

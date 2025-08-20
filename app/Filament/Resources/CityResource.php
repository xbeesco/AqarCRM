<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use App\Models\Country;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المواقع';
    
    protected static ?string $navigationLabel = 'المدن';
    
    protected static ?string $modelLabel = 'مدينة';
    
    protected static ?string $pluralModelLabel = 'المدن';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('بيانات المدينة')
                    ->schema([
                        Select::make('parent_id')
                            ->label('الدولة')
                            ->options(Country::active()->pluck('name_ar', 'id'))
                            ->required()
                            ->searchable(),
                        TextInput::make('name_ar')
                            ->label('الاسم بالعربية')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_en')
                            ->label('الاسم بالإنجليزية')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('مفعل')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country.name_ar')
                    ->label('الدولة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
                Tables\Columns\TextColumn::make('districts_count')
                    ->label('عدد الأحياء')
                    ->counts('districts'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('الدولة')
                    ->options(Country::active()->pluck('name_ar', 'id'))
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('مفعل')
                    ->falseLabel('غير مفعل'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCities::route('/'),
        ];
    }
}
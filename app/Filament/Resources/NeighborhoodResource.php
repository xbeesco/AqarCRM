<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NeighborhoodResource\Pages;
use App\Models\Neighborhood;
use App\Models\District;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NeighborhoodResource extends Resource
{
    protected static ?string $model = Neighborhood::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المواقع';
    
    protected static ?string $navigationLabel = 'المناطق';
    
    protected static ?string $modelLabel = 'منطقة';
    
    protected static ?string $pluralModelLabel = 'المناطق';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('بيانات المنطقة')
                    ->schema([
                        Select::make('parent_id')
                            ->label('الحي')
                            ->options(District::active()->with(['city.country'])->get()->mapWithKeys(function ($district) {
                                return [$district->id => $district->city->country->name_ar . ' - ' . $district->city->name_ar . ' - ' . $district->name_ar];
                            }))
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
                Tables\Columns\TextColumn::make('district.city.country.name_ar')
                    ->label('الدولة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('district.city.name_ar')
                    ->label('المدينة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('district.name_ar')
                    ->label('الحي')
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
                Tables\Columns\TextColumn::make('properties_count')
                    ->label('عدد العقارات')
                    ->counts('properties'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('الحي')
                    ->options(District::active()->with(['city.country'])->get()->mapWithKeys(function ($district) {
                        return [$district->id => $district->city->country->name_ar . ' - ' . $district->city->name_ar . ' - ' . $district->name_ar];
                    }))
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
            'index' => Pages\ManageNeighborhoods::route('/'),
        ];
    }
}
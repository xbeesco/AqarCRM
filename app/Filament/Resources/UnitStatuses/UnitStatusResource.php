<?php

namespace App\Filament\Resources\UnitStatuses;

use App\Filament\Resources\UnitStatuses\Pages\ManageUnitStatuses;
use App\Models\UnitStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitStatusResource extends Resource
{
    protected static ?string $model = UnitStatus::class;

    protected static ?string $recordTitleAttribute = 'name_ar';
    
    protected static ?string $label = 'حالة وحدة';
    
    protected static ?string $pluralLabel = 'حالات الوحدات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_ar')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية'),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                TextColumn::make('color')
                    ->label('اللون')
                    ->badge()
                    ->color(fn (string $state): string => $state),
            ])
            ->searchable(false)
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->bulkActions([])
            ->toggleColumnsTriggerAction(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnitStatuses::route('/'),
        ];
    }
}

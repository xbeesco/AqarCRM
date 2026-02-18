<?php

namespace App\Filament\Resources\UnitFeatures;

use App\Filament\Resources\UnitFeatures\Pages\ManageUnitFeatures;
use App\Models\UnitFeature;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitFeatureResource extends Resource
{
    protected static ?string $model = UnitFeature::class;

    protected static ?string $label = 'ميزة وحدة';

    protected static ?string $pluralLabel = 'مميزات الوحدات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم'),
            ])
            ->searchable(false)
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([])
            ->toggleColumnsTriggerAction(null)
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnitFeatures::route('/'),
        ];
    }
}

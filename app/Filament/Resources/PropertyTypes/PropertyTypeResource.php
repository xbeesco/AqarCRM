<?php

namespace App\Filament\Resources\PropertyTypes;

use App\Filament\Resources\PropertyTypes\Pages\ManagePropertyTypes;
use App\Models\PropertyType;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertyTypeResource extends Resource
{
    protected static ?string $model = PropertyType::class;

    protected static ?string $label = 'نوع عقار';

    protected static ?string $pluralLabel = 'أنواع العقارات';

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
                    ->label('الاسم')
                    ->searchable(),
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
            'index' => ManagePropertyTypes::route('/'),
        ];
    }
}

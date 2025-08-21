<?php

namespace App\Filament\Resources\PropertyStatuses;

use App\Filament\Resources\PropertyStatuses\Pages\ManagePropertyStatuses;
use App\Models\PropertyStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertyStatusResource extends Resource
{
    protected static ?string $model = PropertyStatus::class;
    
    protected static ?string $label = 'حالة عقار';
    
    protected static ?string $pluralLabel = 'حالات العقارات';

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
            ->toggleColumnsTriggerAction(null)
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePropertyStatuses::route('/'),
        ];
    }
}

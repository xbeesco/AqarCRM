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
                TextColumn::make('id')
                    ->label('م')
                    ->sortable(),
                TextColumn::make('name_ar')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
            ])
            ->searchable(false)
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->icon('heroicon-m-pencil-square')
                    ->tooltip('تعديل'),
                DeleteAction::make()
                    ->iconButton()
                    ->icon('heroicon-m-trash')
                    ->tooltip('حذف'),
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

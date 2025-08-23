<?php

namespace App\Filament\Resources\UnitTypes;

use App\Filament\Resources\UnitTypes\Pages\ManageUnitTypes;
use App\Models\UnitType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitTypeResource extends Resource
{
    protected static ?string $model = UnitType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static ?string $navigationLabel = 'أنواع الوحدات';
    
    protected static ?string $modelLabel = 'نوع وحدة';
    
    protected static ?string $pluralModelLabel = 'أنواع الوحدات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->required()
                    ->maxLength(100),
                
                TextInput::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->required()
                    ->maxLength(100),
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
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // إزالة bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnitTypes::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Models\Location;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $recordTitleAttribute = 'name_ar';

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
                Select::make('parent_id')
                    ->label('الموقع الرئيسي')
                    ->relationship('parent', 'name_ar')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_ar')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->formatStateUsing(function ($record) {
                        $level = 0;
                        $current = $record;
                        while ($current->parent) {
                            $level++;
                            $current = $current->parent;
                        }
                        return str_repeat('— ', $level) . $record->name_ar;
                    }),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية'),
                TextColumn::make('level')
                    ->label('المستوى')
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        1 => 'المنطقة',
                        2 => 'المدينة',
                        3 => 'المركز',
                        4 => 'الحي',
                        default => 'غير محدد'
                    }),
            ])
            ->defaultSort('name_ar')
            ->paginated(false)
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
            'index' => ManageLocations::route('/'),
        ];
    }
}

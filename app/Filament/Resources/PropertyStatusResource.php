<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyStatusResource\Pages;
use App\Models\PropertyStatus;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertyStatusResource extends Resource
{
    protected static ?string $model = PropertyStatus::class;

    protected static ?string $navigationLabel = 'حالات العقارات';

    protected static ?string $modelLabel = 'حالة عقار';

    protected static ?string $pluralModelLabel = 'حالات العقارات';

    // Navigation properties removed - managed centrally in AdminPanelProvider

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_ar')
                    ->required()
                    ->label('الاسم بالعربية')
                    ->maxLength(255),
                
                TextInput::make('name_en')
                    ->required()
                    ->label('الاسم بالإنجليزية')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('name_en')
                    ->label('الاسم بالإنجليزية')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropertyStatuses::route('/'),
            'create' => Pages\CreatePropertyStatus::route('/create'),
            'edit' => Pages\EditPropertyStatus::route('/{record}/edit'),
        ];
    }
}

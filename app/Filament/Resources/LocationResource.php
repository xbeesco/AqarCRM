<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationLabel = 'المواقع';

    protected static ?string $pluralModelLabel = 'المواقع';

    protected static ?string $modelLabel = 'موقع';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('level')
                    ->label('المستوى')
                    ->options(Location::getLevelOptions())
                    ->required()
                    ->reactive()
                    ->disabled(fn (?Location $record): bool => $record !== null && $record->exists)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('parent_id', null)),

                Select::make('parent_id')
                    ->label('الموقع الأب')
                    ->options(function (callable $get, ?Location $record): array {
                        $level = $get('level') ?: $record?->level;
                        if (! $level || $level <= 1) {
                            return [];
                        }

                        return Location::getParentOptions($level);
                    })
                    ->visible(fn (callable $get, ?Location $record): bool => ($get('level') ?: $record?->level) > 1)
                    ->required(fn (callable $get, ?Location $record): bool => ($get('level') ?: $record?->level) > 1)
                    ->searchable()
                    ->preload()
                    ->reactive(),

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
                    ->label('الاسم بالعربية')
                    ->formatStateUsing(fn (string $state, Location $record): string => $record->getFormattedTableDisplay())
                    ->html()
                    ->wrap(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(fn (Location $record): string => 'تعديل موقع: '.$record->name)
                    ->modalSubmitActionLabel('حفظ التغييرات')
                    ->modalWidth('xl'),
            ])
            ->defaultSort('path', 'asc')
            ->paginated(false);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLocations::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent', 'children'])
            ->orderBy('path');
    }
}

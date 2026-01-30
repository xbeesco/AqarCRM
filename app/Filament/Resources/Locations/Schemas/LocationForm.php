<?php

namespace App\Filament\Resources\Locations\Schemas;

use App\Models\Location;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
}

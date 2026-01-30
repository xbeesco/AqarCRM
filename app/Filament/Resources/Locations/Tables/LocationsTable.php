<?php

namespace App\Filament\Resources\Locations\Tables;

use App\Models\Location;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
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
}

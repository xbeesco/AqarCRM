<?php

namespace App\Filament\Resources\Properties\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['owner', 'location.parent.parent.parent'])
                    ->withCount('units as total_units');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('اسم العقار')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('اسم المالك')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_units')
                    ->label('الوحدات')
                    ->default(0)
                    ->alignCenter(),

                TextColumn::make('location.name')
                    ->label('الموقع')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if (! $record->location) {
                            return '-';
                        }

                        $path = [];
                        $current = $record->location;

                        while ($current) {
                            array_unshift($path, $current->name);
                            $current = $current->parent;
                        }

                        return implode(' > ', $path);
                    }),
            ])
            ->searchable()
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }
}

<?php

namespace App\Filament\Resources\Units\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('property.name')
                    ->searchable(),
                TextColumn::make('unit_number')
                    ->searchable(),
                TextColumn::make('floor_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('area_sqm')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rooms_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bathrooms_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rent_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_type'),
                TextColumn::make('unit_ranking'),
                TextColumn::make('direction'),
                TextColumn::make('view_type'),
                TextColumn::make('status_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('current_tenant_id')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('furnished')
                    ->boolean(),
                IconColumn::make('has_balcony')
                    ->boolean(),
                IconColumn::make('has_parking')
                    ->boolean(),
                IconColumn::make('has_storage')
                    ->boolean(),
                IconColumn::make('has_maid_room')
                    ->boolean(),
                TextColumn::make('available_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('last_maintenance_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('next_maintenance_date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

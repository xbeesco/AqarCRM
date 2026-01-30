<?php

namespace App\Filament\Resources\Units\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with([
                    'property.location.parent.parent.parent',
                    'unitType',
                    'unitCategory',
                ]);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable()
                    ->tooltip(function ($record): ?string {
                        if (! $record->property || ! $record->property->location) {
                            return null;
                        }

                        $path = [];
                        $current = $record->property->location;

                        while ($current) {
                            array_unshift($path, $current->name);
                            $current = $current->parent;
                        }

                        return 'الموقع: '.implode(' > ', $path);
                    }),

                TextColumn::make('unitType.name_ar')
                    ->label('نوع الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unitCategory.name_ar')
                    ->label('تصنيف الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('area_sqm')
                    ->label('المساحة')
                    ->suffix(' م²')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('rooms_count')
                    ->label('عدد الغرف')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('bathrooms_count')
                    ->label('دورات المياه')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->alignCenter(),

                TextColumn::make('rent_price')
                    ->label('الإيجار الشهري')
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state).' ريال' : '-')
                    ->searchable(query: function ($query, $search) {
                        $monthlyRent = (float) str_replace(',', '', $search);
                        $yearlyRent = $monthlyRent / 12;

                        return $query
                            ->orWhere('rent_price', 'like', '%'.$search.'%')
                            ->orWhere('rent_price', $monthlyRent)
                            ->orWhere('rent_price', $yearlyRent);
                    })
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }
}

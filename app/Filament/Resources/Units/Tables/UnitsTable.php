<?php

namespace App\Filament\Resources\Units\Tables;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
                    'activeContract',
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

                TextColumn::make('unitType.name')
                    ->label('نوع الوحدة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unitCategory.name')
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

                TextColumn::make('occupancy_status')
                    ->label('حالة الإشغال')
                    ->getStateUsing(fn ($record) => $record->occupancy_status->label())
                    ->badge()
                    ->color(fn ($record) => $record->occupancy_status->color())
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('owner_id')
                    ->label('المالك')
                    ->options(function () {
                        return User::where('type', 'owner')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            return $query->whereHas('property', function ($q) use ($data) {
                                $q->where('owner_id', $data['value']);
                            });
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $owner = User::find($data['value']);

                            return $owner ? 'المالك: '.$owner->name : null;
                        }

                        return null;
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->options(function () {
                        return Property::orderBy('name')->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('tenant_id')
                    ->label('المستأجر')
                    ->options(function () {
                        return User::where('type', 'tenant')
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            return $query->whereHas('contracts', function ($q) use ($data) {
                                $q->where('tenant_id', $data['value']);
                            });
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $tenant = User::find($data['value']);

                            return $tenant ? 'المستأجر: '.$tenant->name : null;
                        }

                        return null;
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->maxSelectableRecords(1)
            ->bulkActions([
                BulkAction::make('view_unit_contracts')
                    ->label('عقود الوحدة')
                    ->action(function (Collection $records) {
                        return redirect(UnitContractResource::getUrl('index').'?unit_id='.$records->first()->id);
                    }),
                BulkAction::make('view_collection_payments')
                    ->label('دفعات المستأجرين')
                    ->action(function (Collection $records) {
                        return redirect(CollectionPaymentResource::getUrl('index').'?unit_id='.$records->first()->id);
                    }),
                BulkAction::make('view_expenses')
                    ->label('النفقات')
                    ->action(function (Collection $records) {
                        return redirect(ExpenseResource::getUrl('index').'?unit_id='.$records->first()->id);
                    }),
            ])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }
}

<?php

namespace App\Filament\Resources\Properties\Tables;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\PropertyContracts\PropertyContractResource;
use App\Filament\Resources\SupplyPayments\SupplyPaymentResource;
use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Filament\Resources\Units\UnitResource;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['owner', 'location.parent.parent.parent'])
                    ->withCount('units as total_units')
                    ->withCount(['units as occupied_units' => function ($q) {
                        $q->whereHas('activeContract');
                    }]);
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

                TextColumn::make('occupancy_rate')
                    ->label('نسبة الإشغال')
                    ->getStateUsing(function ($record) {
                        if ($record->total_units === 0) {
                            return '0%';
                        }
                        $rate = round(($record->occupied_units / $record->total_units) * 100);

                        return $rate.'%';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if ($record->total_units === 0) {
                            return 'gray';
                        }
                        $rate = round(($record->occupied_units / $record->total_units) * 100);

                        return match (true) {
                            $rate >= 80 => 'success',
                            $rate >= 50 => 'warning',
                            default => 'danger',
                        };
                    })
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
            ->filters([
                SelectFilter::make('owner_id')
                    ->label('المالك')
                    ->options(function () {
                        return User::where('type', 'owner')
                            ->orderBy('name')
                            ->pluck('name', 'id');
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
                BulkAction::make('view_units')
                    ->label('الوحدات')
                    ->action(function (Collection $records) {
                        return redirect(UnitResource::getUrl('index').'?property_id='.$records->first()->id);
                    }),
                BulkAction::make('view_property_contracts')
                    ->label('عقود العقار')
                    ->action(function (Collection $records) {
                        return redirect(PropertyContractResource::getUrl('index').'?property_id='.$records->first()->id);
                    }),
                BulkAction::make('view_unit_contracts')
                    ->label('عقود الوحدات')
                    ->action(function (Collection $records) {
                        return redirect(UnitContractResource::getUrl('index').'?property_id='.$records->first()->id);
                    }),
                BulkAction::make('view_supply_payments')
                    ->label('دفعات المالك')
                    ->action(function (Collection $records) {
                        return redirect(SupplyPaymentResource::getUrl('index').'?property_id='.$records->first()->id);
                    }),
                BulkAction::make('view_collection_payments')
                    ->label('دفعات المستأجرين')
                    ->action(function (Collection $records) {
                        return redirect(CollectionPaymentResource::getUrl('index').'?property_id='.$records->first()->id);
                    }),
                BulkAction::make('view_expenses')
                    ->label('النفقات')
                    ->action(function (Collection $records) {
                        return redirect(ExpenseResource::getUrl('index').'?property_id='.$records->first()->id);
                    }),
            ])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }
}

<?php

namespace App\Filament\Resources\SupplyPayments\Tables;

use App\Models\Property;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplyPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['propertyContract.property.owner', 'owner']);
            })
            ->columns([
                TextColumn::make('owner.name')
                    ->label('المالك')
                    ->searchable(query: function ($query, $search) {
                        return $query->orWhereHas('owner', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })->orWhereHas('propertyContract.property.owner', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->getStateUsing(function ($record) {
                        return $record->owner?->name ??
                               $record->propertyContract?->property?->owner?->name ??
                               'غير محدد';
                    }),

                TextColumn::make('propertyContract.property.name')
                    ->label('العقار')
                    ->searchable(query: function ($query, $search) {
                        return $query->orWhereHas('propertyContract.property', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('address', 'like', "%{$search}%");
                        });
                    })
                    ->getStateUsing(function ($record) {
                        return $record->propertyContract?->property?->name ?? 'غير محدد';
                    }),

                TextColumn::make('month_year')
                    ->label('الشهر'),

                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('net_amount')
                    ->label('القيمة')
                    ->money('SAR'),

                TextColumn::make('supply_status_label')
                    ->label('الحالة')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->supply_status_label)
                    ->color(fn ($record) => $record->supply_status_color),

                TextColumn::make('delay_reason')
                    ->label('سبب التأجيل')
                    ->placeholder('-')
                    ->visible(fn () => false)
                    ->toggleable(),

                TextColumn::make('paid_date')
                    ->label('تاريخ التوريد')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('delay_duration')
                    ->label('الملاحظات')
                    ->formatStateUsing(function ($record) {
                        if ($record->delay_duration && $record->delay_duration > 0) {
                            $text = $record->delay_duration.' يوم';
                            if ($record->delay_reason) {
                                $text .= ' - السبب: '.$record->delay_reason;
                            }

                            return $text;
                        }

                        return $record->notes ?? '';
                    })
                    ->placeholder('')
                    ->wrap(),
            ])
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

                SelectFilter::make('property')
                    ->label('العقار')
                    ->options(function () {
                        return Property::with('owner')
                            ->get()
                            ->mapWithKeys(function ($property) {
                                return [$property->id => $property->name.' - '.($property->owner?->name ?? 'بدون مالك')];
                            });
                    })
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            return $query->whereHas('propertyContract.property', function ($q) use ($data) {
                                $q->where('id', $data['value']);
                            });
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! empty($data['value'])) {
                            $property = Property::find($data['value']);

                            return $property ? 'العقار: '.$property->name : null;
                        }

                        return null;
                    }),
            ])
            ->deferFilters();
    }
}

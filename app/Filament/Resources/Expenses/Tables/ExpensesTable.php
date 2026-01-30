<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Models\Expense;
use App\Models\Property;
use App\Models\Unit;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('desc')
                    ->label('التوصيف')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('type')
                    ->label('النوع')
                    ->getStateUsing(fn (Expense $record): string => $record->type_name)
                    ->badge()
                    ->color(fn (Expense $record): string => $record->type_color)
                    ->sortable(),

                TextColumn::make('cost')
                    ->label('المبلغ')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('docs_count')
                    ->label('الإثباتات')
                    ->getStateUsing(fn (Expense $record): string => $record->docs_count.' إثبات')
                    ->badge()
                    ->color('info'),

                TextColumn::make('subject_name')
                    ->label('مرتبطة بـ')
                    ->getStateUsing(function (Expense $record): string {
                        return $record->subject_name;
                    })
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('property_and_unit')
                    ->label('العقار')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('property_id')
                                ->label('العقار')
                                ->options(Property::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('unit_id', null)),

                            Select::make('unit_id')
                                ->label('خاص بـ')
                                ->native(true)
                                ->placeholder('العقار نفسه')
                                ->options(function ($get) {
                                    $propertyId = $get('property_id');
                                    if (! $propertyId) {
                                        return [];
                                    }
                                    $units = Unit::where('property_id', $propertyId)
                                        ->pluck('name', 'id')
                                        ->toArray();

                                    return $units;
                                })
                                ->visible(fn ($get) => (bool) $get('property_id')),
                        ]),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        if (isset($data['property_id']) && $data['property_id']) {
                            if (isset($data['unit_id']) && $data['unit_id']) {
                                // Expenses for a specific unit
                                $query->where('subject_type', 'App\\Models\\Unit')
                                    ->where('subject_id', $data['unit_id']);
                            } else {
                                // Expenses for the property itself only (not units)
                                $query->where('subject_type', 'App\\Models\\Property')
                                    ->where('subject_id', $data['property_id']);
                            }
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (isset($data['property_id']) && $data['property_id']) {
                            $property = Property::find($data['property_id']);
                            if ($property) {
                                if (isset($data['unit_id']) && $data['unit_id']) {
                                    $unit = Unit::find($data['unit_id']);
                                    if ($unit) {
                                        $indicators['filter'] = 'العقار: '.$property->name.' - خاص بـ: '.$unit->name;
                                    }
                                } else {
                                    $indicators['filter'] = 'العقار: '.$property->name.' (العقار ككل)';
                                }
                            }
                        }

                        return $indicators;
                    }),

                // Expense type filter
                SelectFilter::make('type')
                    ->label('نوع النفقة')
                    ->options(Expense::TYPES)
                    ->multiple(),

                // Period filter (month and year)
                Filter::make('period')
                    ->label('الفترة')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('this_month')
                                ->label('هذا الشهر')
                                ->inline(false),
                            Toggle::make('this_year')
                                ->label('هذا العام')
                                ->inline(false),
                        ]),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        if (isset($data['this_month']) && $data['this_month']) {
                            $query->thisMonth();
                        }
                        if (isset($data['this_year']) && $data['this_year']) {
                            $query->thisYear();
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (isset($data['this_month']) && $data['this_month']) {
                            $indicators['this_month'] = 'هذا الشهر';
                        }
                        if (isset($data['this_year']) && $data['this_year']) {
                            $indicators['this_year'] = 'هذا العام';
                        }

                        return $indicators;
                    }),

                // Date range filter (from - to)
                Filter::make('date_range')
                    ->label('نطاق التاريخ')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('from_date')
                                ->label('من تاريخ'),
                            DatePicker::make('to_date')
                                ->label('إلى تاريخ'),
                        ]),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (isset($data['from_date']) && $data['from_date']) {
                            $indicators['from_date'] = 'من: '.Carbon::parse($data['from_date'])->format('Y-m-d');
                        }
                        if (isset($data['to_date']) && $data['to_date']) {
                            $indicators['to_date'] = 'إلى: '.Carbon::parse($data['to_date'])->format('Y-m-d');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('date', 'desc');
    }
}

<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Unit;
use Filament\Forms;

class VacantUnitsWidget extends BaseWidget
{
    protected static ?string $heading = 'العقارات التي بها وحدات فارغة';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Unit::whereDoesntHave('activeContract')
                    ->with(['property', 'unitType', 'unitCategory', 'property.location'])
                    ->orderBy('property_id')
                    ->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('unitType.name')
                    ->label('النوع')
                    ->badge()
                    ->color('success')
                    ->default('غير محدد'),
                    
                Tables\Columns\TextColumn::make('unitCategory.name')
                    ->label('التصنيف')
                    ->badge()
                    ->color('warning')
                    ->default('غير محدد'),
                    
                Tables\Columns\TextColumn::make('annual_rent')
                    ->label('الإيجار السنوي')
                    ->getStateUsing(fn ($record) => $record->rent_price * 12)
                    ->money('SAR')
                    ->alignCenter()
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->defaultGroup(
                Group::make('property.name')
                    ->label('العقار')
                    ->collapsible()
            )
            ->defaultSort('property.name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('unit_type_id')
                    ->label('نوع الوحدة')
                    ->relationship('unitType', 'name_ar')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('unit_category_id')
                    ->label('تصنيف الوحدة')
                    ->relationship('unitCategory', 'name_ar')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('rent_range')
                    ->form([
                        Forms\Components\TextInput::make('rent_from')
                            ->label('السعر من')
                            ->numeric()
                            ->prefix('ريال'),
                        Forms\Components\TextInput::make('rent_to')
                            ->label('السعر إلى')
                            ->numeric()
                            ->prefix('ريال'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['rent_from'], fn ($q) => $q->where('rent_price', '>=', $data['rent_from']))
                            ->when($data['rent_to'], fn ($q) => $q->where('rent_price', '<=', $data['rent_to']));
                    }),
            ])
            ->paginated([10, 25, 50])
            ->striped()
            ->poll('30s')
            ->emptyStateHeading('لا توجد وحدات فارغة')
            ->emptyStateDescription('جميع الوحدات مؤجرة حالياً')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
    
    protected function getTableHeading(): ?string
    {
        $totalVacant = Unit::whereDoesntHave('activeContract')->count();
        $totalAnnualRent = Unit::whereDoesntHave('activeContract')->sum('rent_price') * 12;
        
        $formattedRent = number_format($totalAnnualRent, 2) . ' ريال';
        
        return static::$heading . " ({$totalVacant} وحدة - القيمة السنوية: {$formattedRent})";
    }
}
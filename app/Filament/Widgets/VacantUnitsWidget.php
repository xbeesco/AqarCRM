<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                    
                TextColumn::make('property.name')
                    ->label('العقار'),
                    
                TextColumn::make('name')
                    ->label('الوحدة'),
                    
                TextColumn::make('unitType.name')
                    ->label('النوع'),
                    
                TextColumn::make('unitCategory.name')
                    ->label('التصنيف'),
                    
                TextColumn::make('annual_rent')
                    ->label('الإيجار السنوي')
                    ->getStateUsing(fn ($record) => $record->rent_price * 12)
                    ->money('SAR'),
            ])
            ->defaultSort('property_id', 'asc')
            ->filters([
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('unit_type_id')
                    ->label('نوع الوحدة')
                    ->relationship('unitType', 'name_ar')
                    ->searchable()
                    ->preload(),
            ])
            ->paginated([10, 25, 50])
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
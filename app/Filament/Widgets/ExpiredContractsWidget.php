<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\UnitContract;
use Carbon\Carbon;
use Filament\Forms;

class ExpiredContractsWidget extends BaseWidget
{
    protected static ?string $heading = '  العقود المنتهية';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;
    
    protected function getToday(): Carbon
    {
        return env('TEST_DATE') ? 
            Carbon::parse(env('TEST_DATE'))->startOfDay() : 
            Carbon::today();
    }
    
    public function table(Table $table): Table
    {
        $today = $this->getToday();
        
        return $table
            ->query(
                UnitContract::with(['tenant', 'property', 'unit'])
                    ->where('end_date', '<=', $today)
                    ->where('contract_status', '!=', 'terminated')
                    ->orderBy('end_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('contract_number')
                    ->label('رقم العقد')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم العقد')
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('property.name')
                    ->label('اسم العقار')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('اسم الوحدة')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('اسم المستأجر')
                    ->searchable()
                    ->description(fn ($record) => $record->tenant?->phone ?? '-'),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ انتهاء العقد')
                    ->date('Y-m-d')
                    ->sortable()
                    ->color('danger')
                    ->weight('bold')
                    ->icon('heroicon-o-calendar'),
                   // مش عاوزه دي هسيبها لو اتطلبت 
                // Tables\Columns\TextColumn::make('days_expired')
                //     ->label('منتهي منذ')
                //     ->getStateUsing(function ($record) use ($today) {
                //         return $today->diffInDays($record->end_date) . ' يوم';
                //    })
                //     ->badge()
                //     ->color(fn ($state) => 
                //         intval($state) > 30 ? 'danger' : 'warning'
                //     ),
                    
            //     Tables\Columns\BadgeColumn::make('contract_status')
            //         ->label('الحالة')
            //         ->formatStateUsing(fn ($state) => match($state) {
            //             'active' => 'نشط (منتهي)',
            //             'expired' => 'منتهي',
            //             'terminated' => 'ملغي',
            //             default => $state
            //         })
            //         ->color('danger')
            //         ->icon('heroicon-o-x-circle'),
            ])
            ->defaultGroup(
                Group::make('property.name')
                    ->label('العقار')
                    ->collapsible()
            )
             ->defaultSort('end_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('expired_over_30')
                    ->label('منتهي منذ أكثر من 30 يوم')
                    ->query(fn ($query) => $query->where('end_date', '<', Carbon::today()->subDays(30))),
                    
                Tables\Filters\Filter::make('expired_over_60')
                    ->label('منتهي منذ أكثر من 60 يوم')
                    ->query(fn ($query) => $query->where('end_date', '<', Carbon::today()->subDays(60))),
            ])
            ->paginated([10, 25, 50])
            ->striped()
            ->poll('30s')
            ->emptyStateHeading('لا توجد عقود منتهية')
            ->emptyStateDescription('جميع العقود سارية أو تم تجديدها')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
    
    protected function getTableHeading(): ?string
    {
        $today = $this->getToday();
            
        $totalExpired = UnitContract::where('end_date', '<', $today)
            ->where('contract_status', '!=', 'terminated')
            ->count();
            
        $criticalExpired = UnitContract::where('end_date', '<', $today->copy()->subDays(30))
            ->where('contract_status', '!=', 'terminated')
            ->count();
        
        return static::$heading . " ({$totalExpired} عقد - {$criticalExpired} حرج)";
    }
}
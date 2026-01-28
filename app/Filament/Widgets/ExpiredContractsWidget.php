<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Models\UnitContract;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpiredContractsWidget extends BaseWidget
{
    protected static ?string $heading = '  العقود المنتهية';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected function getToday(): Carbon
    {
        return Carbon::now()->copy()->startOfDay();
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
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('contract_number')
                    ->label('رقم العقد'),

                TextColumn::make('property.name')
                    ->label('العقار'),

                TextColumn::make('unit.name')
                    ->label('الوحدة'),

                TextColumn::make('tenant.name')
                    ->label('المستأجر'),

                TextColumn::make('tenant.phone')
                    ->label('الهاتف'),

                TextColumn::make('end_date')
                    ->label('انتهى في')
                    ->date('Y-m-d')
                    ->color('danger'),
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
                SelectFilter::make('property_id')
                    ->label('العقار')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('expired_over_30')
                    ->label('منتهي منذ أكثر من 30 يوم')
                    ->query(fn ($query) => $query->where('end_date', '<', $this->getToday()->subDays(30))),

                Filter::make('expired_over_60')
                    ->label('منتهي منذ أكثر من 60 يوم')
                    ->query(fn ($query) => $query->where('end_date', '<', $this->getToday()->subDays(60))),
            ])
            ->paginated([10, 25, 50])
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

        return static::$heading." ({$totalExpired} عقد - {$criticalExpired} حرج)";
    }
}

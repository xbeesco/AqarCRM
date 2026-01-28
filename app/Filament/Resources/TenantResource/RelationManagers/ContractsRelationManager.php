<?php

namespace App\Filament\Resources\TenantResource\RelationManagers;

use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\UnitContract;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'unitContracts';

    protected static ?string $title = 'العقود';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('contract_number')
            ->columns([
                TextColumn::make('contract_number')
                    ->label('رقم العقد')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.property.name')
                    ->label('العقار')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('الوحدة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('monthly_rent')
                    ->label('الإيجار الشهري')
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('بداية العقد')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('نهاية العقد')
                    ->date('Y-m-d')
                    ->sortable(),
                BadgeColumn::make('contract_status')
                    ->label('حالة العقد')
                    ->colors([
                        'primary' => 'draft',
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger' => 'expired',
                        'secondary' => 'terminated',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'مسودة',
                        'active' => 'نشط',
                        'pending' => 'قيد الانتظار',
                        'expired' => 'منتهي',
                        'terminated' => 'منهي',
                        default => $state
                    }),
                TextColumn::make('remaining_days')
                    ->label('الأيام المتبقية')
                    ->state(function (UnitContract $record) {
                        if ($record->contract_status !== 'active') {
                            return '-';
                        }
                        $days = now()->diffInDays($record->end_date, false);

                        return $days > 0 ? $days.' يوم' : 'منتهي';
                    })
                    ->badge()
                    ->color(function (UnitContract $record) {
                        if ($record->contract_status !== 'active') {
                            return 'gray';
                        }
                        $days = now()->diffInDays($record->end_date, false);
                        if ($days <= 0) {
                            return 'danger';
                        }
                        if ($days <= 30) {
                            return 'warning';
                        }

                        return 'success';
                    }),
            ])
            ->filters([
                SelectFilter::make('contract_status')
                    ->label('حالة العقد')
                    ->options([
                        'draft' => 'مسودة',
                        'active' => 'نشط',
                        'pending' => 'قيد الانتظار',
                        'expired' => 'منتهي',
                        'terminated' => 'منهي',
                    ]),
            ])
            ->headerActions([
                // Can add buttons to create new contract here if needed
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

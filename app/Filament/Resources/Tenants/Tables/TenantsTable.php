<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('التليفون 1')
                    ->searchable(),

                TextColumn::make('secondary_phone')
                    ->label('التليفون 2')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('الإيميل')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(12)
            ->recordActions([
                ViewAction::make()
                    ->label('تقرير')
                    ->icon('heroicon-o-document-text'),
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
                ActionGroup::make([
                    Action::make('view_units')
                        ->label('الوحدات')
                        ->icon('heroicon-o-home')
                        ->url(fn ($record) => UnitResource::getUrl('index').'?tenant_id='.$record->id),
                    Action::make('view_unit_contracts')
                        ->label('عقود الوحدات')
                        ->icon('heroicon-o-document-text')
                        ->url(fn ($record) => UnitContractResource::getUrl('index').'?tenant_id='.$record->id),
                    Action::make('view_collection_payments')
                        ->label('دفعات المستأجر')
                        ->icon('heroicon-o-currency-dollar')
                        ->url(fn ($record) => CollectionPaymentResource::getUrl('index').'?tenant_id='.$record->id),
                ])
                    ->label('المزيد')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}

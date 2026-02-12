<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use App\Filament\Resources\UnitContracts\UnitContractResource;
use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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
            ])
            ->maxSelectableRecords(1)
            ->bulkActions([
                BulkAction::make('view_units')
                    ->label('الوحدات')
                    ->action(function (Collection $records) {
                        return redirect(UnitResource::getUrl('index').'?tenant_id='.$records->first()->id);
                    }),
                BulkAction::make('view_unit_contracts')
                    ->label('عقود الوحدات')
                    ->action(function (Collection $records) {
                        return redirect(UnitContractResource::getUrl('index').'?tenant_id='.$records->first()->id);
                    }),
                BulkAction::make('view_collection_payments')
                    ->label('دفعات المستأجر')
                    ->action(function (Collection $records) {
                        return redirect(CollectionPaymentResource::getUrl('index').'?tenant_id='.$records->first()->id);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
